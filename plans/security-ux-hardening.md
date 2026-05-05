# ARTERI Security & UX Hardening Plan

## Tujuan
1. **Validasi** di setiap input pada setiap fungsi — tidak ada input tanpa validasi
2. **Error handling** yang baik — user selalu tahu apa yang salah, dalam Bahasa Indonesia
3. **File access control** — file upload tidak bisa diakses langsung, hanya lewat API dengan pengecekan hak akses
4. **Audit log** — semua aktivitas tercatat, login attempt (sukses/gagal), view khusus admin
5. **Keamanan** — CSRF aktif, rate limiting, session hardening, password policy

---

## Arsitektur: Trait-Based Enhancement

Tidak refactor besar-besaran. Tambahkan trait + perkuat `BaseController` — ikuti pola yang sudah ada.

### File Baru yang Dibuat
| File | Fungsi |
|------|--------|
| `app/Traits/AuditableTrait.php` | Log otomatis ke system_log |
| `app/Traits/JsonResponseTrait.php` | Standarisasi JSON response semua endpoint AJAX |
| `app/Models/SystemLogModel.php` | Model untuk tabel system_log |
| `app/Controllers/AuditLog.php` | Controller viewer audit log (admin only) |
| `app/Controllers/FileController.php` | Controller untuk serve file upload dengan ACL |
| `app/Database/Migrations/2026-05-05-000001_UpgradeSystemLog.php` | Alter system_log: tambah aksi, tabel, record_id, detail, ip_address |
| `app/Database/Migrations/2026-05-05-000002_CreateLoginAttempts.php` | Tabel untuk rate limiting login |
| `app/Language/id/Validation.php` | Terjemahan error CI4 → Bahasa Indonesia |
| `app/Views/audit/index.php` | View daftar audit log |
| `app/Views/audit/detail.php` | View detail satu log entry |

### File yang Dimodifikasi
| File | Perubahan |
|------|-----------|
| `app/Controllers/BaseController.php` | Gunakan kedua trait, tambah helper translate error |
| `app/Controllers/Auth.php` | Validasi input, Throttler, session regenerate, logging |
| `app/Controllers/Arsip.php` | Validasi lengkap, FK checks, logging, pakai trait |
| `app/Controllers/Sirkulasi.php` | Validasi lengkap, date range, FK checks, logging |
| `app/Controllers/MasterData.php` | Tambah max_length + is_unique, pakai trait |
| `app/Controllers/User.php` | CI4 validator, password policy, null hash fix, logging |
| `app/Controllers/Import.php` | Validasi file + isi, transaction, per-row error |
| `app/Controllers/Home.php` | Logging, offset validation |
| `app/Config/Validation.php` | Rule groups + custom rules (valid_fk, valid_date_range, valid_password_strength) |
| `app/Config/App.php` | `$defaultLocale = 'id'` |
| `app/Config/Filters.php` | Aktifkan CSRF global |
| `app/Config/Routes.php` | Tambah route file serve + audit log |
| `app/Views/layout/header.php` | Tambah menu Audit Log (admin only) |
| `app/Views/arsip/form.php` | Render validation errors, hapus helper functions (pindah ke Helpers) |
| `app/Views/sirkulasi/form.php` | Render validation errors |
| `public/js/custom.js` | CSRF token auto-inject, proper JSON error handling, toast notifikasi |

---

## PHASE 1: Foundation

### 1a. Upgrade `system_log` table
**File**: `app/Database/Migrations/2026-05-05-000001_UpgradeSystemLog.php`

Alter table tambah kolom:
- `aksi` VARCHAR(50) — `LOGIN_SUCCESS`, `LOGIN_FAILED`, `LOGOUT`, `CREATE`, `UPDATE`, `DELETE`, `FILE_DELETE`, `DOWNLOAD`, `IMPORT`, `EXPORT`, `VIEW`, `RETURN`
- `tabel` VARCHAR(100) — nama tabel target
- `record_id` INT NULL — id record yang terpengaruh
- `detail` TEXT NULL — JSON data perubahan / error detail
- `ip_address` VARCHAR(45)

### 1b. Create `login_attempts` table
**File**: `app/Database/Migrations/2026-05-05-000002_CreateLoginAttempts.php`

```sql
CREATE TABLE login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME NOT NULL,
    KEY (username, attempted_at)
)
```

### 1c. `SystemLogModel`
**File**: `app/Models/SystemLogModel.php`

Extend `CodeIgniter\Model`, `$allowedFields` lengkap, method `log()` dan `search()`.

### 1d. `AuditableTrait`
**File**: `app/Traits/AuditableTrait.php`

```php
trait AuditableTrait {
    protected function logActivity(string $aksi, string $tabel, ?int $recordId = null, ?array $detail = null): void;
    protected function logCrud(string $action, string $table, int $id, ?array $old = null, ?array $new = null): void;
    protected function logLogin(string $username, bool $success, ?string $failReason = null): void;
}
```

### 1e. `JsonResponseTrait`
**File**: `app/Traits/JsonResponseTrait.php`

Standarisasi SEMUA endpoint AJAX ke format:
```json
{"status": "success", "message": "Data berhasil disimpan."}
{"status": "error", "message": "Gagal menyimpan data.", "errors": {"noarsip": "No. Arsip harus diisi."}}
```

Methods:
- `jsonSuccess(string $message, ?array $data = null)` → HTTP 200
- `jsonError(string $message, array $errors = [])` → HTTP 422
- `jsonValidationErrors()` → auto-read `$this->validator->getErrors()` + translate

### 1f. Enhanced `BaseController`
**File**: `app/Controllers/BaseController.php`

```php
abstract class BaseController extends Controller
{
    use \App\Traits\AuditableTrait;
    use \App\Traits\JsonResponseTrait;
    
    protected $helpers = ['form', 'url', 'acl'];
    
    protected function errors(): array;
    protected function redirectWithErrors(): RedirectResponse;
}
```

### 1g. Language File
**File**: `app/Language/id/Validation.php`

```php
return [
    'required'      => '{field} harus diisi.',
    'min_length'    => '{field} minimal {param} karakter.',
    'max_length'    => '{field} maksimal {param} karakter.',
    'is_unique'     => '{field} sudah terpakai.',
    'integer'       => '{field} harus berupa angka.',
    'in_list'       => '{field} tidak valid.',
    'valid_date'    => '{field} format tanggal tidak valid (YYYY-MM-DD).',
    'matches'       => '{field} tidak cocok dengan {param}.',
    'greater_than'  => '{field} harus lebih besar dari {param}.',
    'ext_in'        => '{field} harus berekstensi: {param}.',
    'max_size'      => '{field} maksimal {param} KB.',
    'valid_fk'      => '{field} tidak ditemukan di database.',
    'valid_date_range' => '{field} harus setelah atau sama dengan {param}.',
    'valid_password_strength' => '{field} minimal 8 karakter, mengandung huruf dan angka.',
    'alpha_numeric' => '{field} hanya boleh huruf dan angka.',
];
```

### 1h. Config Changes
- `app/Config/App.php`: `$defaultLocale = 'id'`
- `app/Config/Validation.php`: tambah semua rule groups + custom rules (`valid_fk`, `valid_date_range`, `valid_password_strength`)
- `app/Config/Filters.php`: tambahkan `'csrf' => \CodeIgniter\Filters\CSRF::class` ke global `before`

---

## PHASE 2: Validation Hardening — Per Controller

### 2a. `Auth` Controller (`app/Controllers/Auth.php`)

| Method | Perubahan |
|--------|-----------|
| `login()` | Tidak berubah |
| `doLogin()` | **Validasi**: `username` required, `password` required. **Rate limit**: cek `login_attempts` — 5 gagal/15 menit per username → tolak. **Session**: regenerate setelah sukses. **Log**: `LOGIN_SUCCESS` / `LOGIN_FAILED`. Fix typo: `erorlogin` → `error_login`. |
| `logout()` | `session()->destroy()` + regenerate. Log `LOGOUT`. |

### 2b. `Arsip` Controller (`app/Controllers/Arsip.php`)

| Method | Perubahan |
|--------|-----------|
| `create()` | Validasi lewat `$this->validate($rules)`. FK existence via custom rule. Transaction wrap. Log `CREATE`. File path ke `writable/uploads/arsip/`. Error render di view. |
| `update($id)` | Validasi `$id` integer + exists. Rule sama, `is_unique` abaikan self. Log `UPDATE`. |
| `delete($id)` | Validasi `$id` exists. Log `DELETE`. Return JSON proper. |
| `deleteFile($id)` | Validasi `$id` exists. Log `FILE_DELETE`. Return JSON proper. |

### 2c. `Sirkulasi` Controller (`app/Controllers/Sirkulasi.php`)

| Method | Perubahan |
|--------|-----------|
| `create()` | **Validasi tambahan**: `valid_date_range[tgl_pinjam,tgl_haruskembali]`, `valid_fk[data_arsip,noarsip]`, `valid_fk[master_user,username]`. Log `CREATE`. |
| `update($id)` | Validasi `$id` exists. Sama seperti create. Log `UPDATE`. |
| `delete($id)` | Validasi `$id` exists. Log `DELETE`. JSON proper. |
| `kembali($id)` | Validasi `$id` exists + belum kembali. Log `RETURN`. |

### 2d. `MasterData` Controller (`app/Controllers/MasterData.php`)

- Tambah `max_length[255]` + `is_unique` ke semua field `nama` di `$entities`
- Semua method create/update/delete gunakan `JsonResponseTrait`
- Log semua CRUD dengan `$this->logActivity()`

### 2e. `User` Controller (`app/Controllers/User.php`)

| Method | Perubahan |
|--------|-----------|
| `create()` | **CI4 validator**: `username` required\|alpha_numeric\|min_length[3]\|is_unique, `password` required\|min_length[8]\|valid_password_strength, `conf_password` required\|matches[password], `tipe` required\|in_list[admin,user]. Return via `$this->jsonValidationErrors()`. Log `CREATE`. |
| `update()` | Sama rules, `password` permit_empty. **Fix null hash bug**: `if (! empty($password))`. Validasi `$id` exists. Log `UPDATE`. |
| `delete()` | Validasi `$id` exists + bukan admin terakhir + bukan self-delete. Log `DELETE`. |

### 2f. `Import` Controller (`app/Controllers/Import.php`)

- **Validasi file**: required, ext_in[xlsx,xls,csv], max_size
- **Validasi header**: kolom wajib ada sesuai aturan arsip
- **Per-row validation**: tiap baris divalidasi dengan rule arsip
- **Transaction**: semua insert dalam satu transaksi, rollback jika ada error
- **Error collection**: kumpulkan error per baris → flash detail ke view
- Log `IMPORT` dengan summary count

### 2g. `Home` Controller (`app/Controllers/Home.php`)

| Method | Perubahan |
|--------|-----------|
| `search($offset)` | Validasi `$offset` non-negative int, clamp ke 0 jika invalid |
| `detail($id)` | Validasi `$id` numeric. Log `VIEW`. |
| `download()` | Validasi filter params. Log `EXPORT`. |

---

## PHASE 3: File Access Control

### 3a. Pindah Upload Directory
- Ubah path: `FCPATH . 'uploads/arsip/'` → `WRITEPATH . 'uploads/arsip/'`
- Update semua referensi di `Arsip::create()`, `update()`, `delete()`, `deleteFile()`
- **Tidak ada lagi akses langsung** — writable dilindungi `.htaccess` (`Deny from all`)

### 3b. `FileController`
**File**: `app/Controllers/FileController.php`

```php
class FileController extends BaseController
{
    public function serve(string $filename): ResponseInterface
    {
        if (! session('username')) return redirect()->to('login');
        if (! hasModuleAccess('arsip')) return redirect()->to('/');
        
        $path = WRITEPATH . 'uploads/arsip/' . basename($filename);
        if (! is_file($path)) throw PageNotFoundException::forPageNotFound();
        
        $this->logActivity('DOWNLOAD', 'data_arsip', null, ['file' => $filename]);
        return $this->response->download($path, null);
    }
}
```

### 3c. Route
```php
$routes->get('file/(:segment)', 'FileController::serve/$1');
```

### 3d. Update Views
- `home/detail.php`: `base_url('files/...)` → `base_url('file/...)`
- `home/search.php`: sama
- `arsip/form.php`: sama

### 3e. Hapus Helper dari View
- Pindahkan `_return_bytes()` dan `_max_file_upload_in_bytes()` dari `app/Views/arsip/form.php` ke `app/Helpers/file_helper.php`

---

## PHASE 4: AJAX Error Handling & UX

### 4a. Refactor `custom.js`

**CSRF Auto-Inject**:
```javascript
$(function() {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });
});
```

**Global AJAX Error Handler**:
```javascript
$(document).ajaxError(function(event, jqxhr) {
    if (jqxhr.status === 403) {
        showToast('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
        setTimeout(function() { window.location = site_url + '/login'; }, 2000);
    } else {
        var msg = 'Terjadi kesalahan. Silakan coba lagi.';
        try { var resp = JSON.parse(jqxhr.responseText); if (resp.message) msg = resp.message; } catch(e) {}
        showToast('error', msg);
    }
});
```

**Toast Notification**:
```javascript
function showToast(type, message) {
    // Render Bootstrap 3 alert di top-center, auto-hide 5 detik
}
```

**Refactor Semua `ajaxForm` Callback**:
- Setiap `success` handler parse response, cek `status`
- Jika error → tampilkan di modal error div (bukan `alert()`)
- Jika sukses → toast sukses + reload table
- Ganti semua `alert()` dengan `showToast()` / inline error

### 4b. Render Validation Errors di Form Views
**File**: `app/Views/arsip/form.php` dan `app/Views/sirkulasi/form.php`

Tambahkan di atas form:
```php
<?php if (session('errors')): ?>
    <div class="alert alert-danger">
        <ul>
        <?php foreach (session('errors') as $error): ?>
            <li><?= esc($error) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
```

### 4c. Modal Error Rendering
Semua modal form tambahkan `<div class="modal-errors alert alert-danger" style="display:none"></div>`. JavaScript render error di situ.

---

## PHASE 5: Audit Log Viewer

### 5a. `AuditLog` Controller
**File**: `app/Controllers/AuditLog.php`

Admin only. Paginasi, filter by aksi/tabel/username/tanggal. Detail view dengan JSON detail.

### 5b. Routes
```php
$routes->get('audit', 'AuditLog::index');
$routes->get('audit/(:num)', 'AuditLog::detail/$1');
```

### 5c. Views
- `app/Views/audit/index.php`: Tabel + filter bar + paginasi
- `app/Views/audit/detail.php`: Detail lengkap

### 5d. Menu di Header
```php
<?php if (isAdmin()): ?>
    <li><a href="<?= site_url('audit') ?>"><i class="glyphicon glyphicon-list-alt"></i> Audit Log</a></li>
<?php endif; ?>
```

---

## PHASE 6: Keamanan Tambahan

### 6a. Rate Limiting Login
Di `Auth::doLogin()` — gunakan CI4 `Throttler` service: 5 percobaan/15 menit per username+IP.

### 6b. CSRF Global
`app/Config/Filters.php`: CSRF di semua route. Tambah `<?= csrf_field() ?>` di `auth/login.php`.

### 6c. Password Policy
Custom rule `valid_password_strength`: minimal 8 karakter, harus ada huruf DAN angka.

### 6d. .htaccess Reinforcement
Update `public/.htaccess` — blokir akses langsung ke file sensitif.

---

## Verification Checklist

### Login
- [ ] Login sukses → system_log ada `LOGIN_SUCCESS`
- [ ] Login gagal → error Bahasa Indonesia, system_log `LOGIN_FAILED`
- [ ] 6x gagal → rate limit aktif
- [ ] Logout → session hancur, system_log `LOGOUT`

### CSRF
- [ ] Submit form tanpa token → 403
- [ ] AJAX auto-inject token

### Validasi Form
- [ ] Arsip: field kosong → error tampil di view
- [ ] Arsip: noarsip duplicate → "sudah terpakai"
- [ ] Arsip: file .exe → "harus berekstensi pdf, doc, docx"
- [ ] Sirkulasi: tgl_kembali < tgl_pinjam → error
- [ ] User: password pendek → "minimal 8 karakter"
- [ ] User: password hanya huruf → error

### File Access
- [ ] Akses langsung `/uploads/arsip/...` → 403/404
- [ ] Akses via `/file/...` (login) → download
- [ ] Akses via `/file/...` (belum login) → redirect login
- [ ] Akses via `/file/...` (user tanpa akses modul arsip) → redirect home

### Audit Log
- [ ] Admin bisa akses `/audit`
- [ ] User biasa tidak bisa
- [ ] Filter berfungsi

### AJAX UX
- [ ] Error modal → tampil di modal (bukan alert)
- [ ] Server error → toast
- [ ] Sukses → toast
- [ ] Tidak ada `alert()` tersisa

### Import
- [ ] File bukan Excel → error
- [ ] Header salah → error deskriptif
- [ ] Data valid → sukses + count
- [ ] Partial error → rollback + tampilkan per baris

---

## Urutan Implementasi
1. **Phase 1** — Foundation (trait, model, migration, language, config)
2. **Phase 6** — Keamanan (CSRF, rate limit, password policy)
3. **Phase 3** — File access control
4. **Phase 2** — Validation hardening semua controller
5. **Phase 4** — AJAX UX refactor
6. **Phase 5** — Audit log viewer
