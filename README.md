# Arteri 2

Arteri 2 adalah aplikasi pengelola arsip digital berbasis CodeIgniter 4 untuk pencatatan, pencarian, pengelolaan, peminjaman, audit, impor, ekspor, dan pengamanan akses arsip.

Proyek ini adalah kelanjutan dari [Arteri](https://github.com/dicarve/arteri), dengan target runtime PHP yang lebih baru, basis aplikasi yang lebih stabil, dan praktik keamanan yang lebih kuat untuk penggunaan publik.

## 📸 Screenshots & Feature Demo

**[Lihat FEATURES.md untuk screenshot lengkap dan demo fitur](FEATURES.md)**

Demo mencakup:
- Dashboard analytics dengan charts
- Master data management
- CRUD arsip dengan file upload
- Search & advanced filters
- Sirkulasi peminjaman
- Reports & export
- Trash & recovery
- Mobile responsive
- REST API documentation

## Fitur Utama

- Manajemen arsip digital beserta metadata arsip.
- Pencarian dan filter arsip.
- Master data kode klasifikasi, lokasi, media, pencipta, dan unit pengolah.
- Manajemen pengguna dan kontrol akses berbasis peran.
- Sirkulasi atau peminjaman arsip.
- Impor dan ekspor data arsip.
- Audit log untuk aktivitas penting.
- Proteksi dasar aplikasi web seperti autentikasi, CSRF, validasi input, dan test keamanan OWASP.

## Arsitektur Aplikasi

### Struktur Folder

```
app/
├── Config/          # Konfigurasi aplikasi (Database, Routes, dll)
├── Controllers/     # Controller untuk handling request HTTP
├── Database/        # Migration dan Seeder
├── Helpers/         # Helper functions
├── Libraries/       # Library custom
├── Models/          # Model untuk akses database
├── Views/           # Template view (layout, partials)
│   ├── layout/      # Template utama (header, footer)
│   ├── arsip/       # View halaman arsip
│   ├── sirkulasi/   # View halaman sirkulasi
│   ├── master/      # View master data
│   └── user/        # View manajemen user
public/
├── css/             # CSS stylesheets
├── js/              # JavaScript files (custom.js, plugins)
├── images/          # Gambar statis
├── uploads/         # File upload pengguna
└── index.php        # Entry point aplikasi
```

### Database Schema

**Tabel Utama:**
- `data_arsip` - Data arsip dengan metadata lengkap
- `sirkulasi` - Record peminjaman arsip
- `master_kode` - Klasifikasi kode arsip
- `master_lokasi` - Lokasi penyimpanan arsip
- `master_media` - Tipe media penyimpanan
- `master_pencipta` - Informasi pencipta arsip
- `master_pengolah` - Unit pengolah arsip
- `master_user` - Data pengguna aplikasi
- `system_log` - Log aktivitas sistem
- `login_attempts` - Log percobaan login

### API Endpoints

**Autocomplete:**
- `GET /ajax/arsip/{keywords}` - Cari arsip untuk autocomplete
- `GET /ajax/user/{keywords}` - Cari user untuk autocomplete

**Master Data:**
- `GET /master/klas` - Daftar klasifikasi
- `GET /master/penc` - Daftar pencipta
- `GET /master/pengolah` - Daftar unit pengolah
- `GET /master/lokasi` - Daftar lokasi
- `GET /master/media` - Daftar media

### Autentikasi & Otorisasi

- Session-based authentication dengan CSRF protection
- Role-based access control (admin/user)
- Akses klasifikasi per user (`akses_klas`)
- Modul access per user (`akses_modul`)
- Login attempt tracking untuk keamanan

### API Documentation

#### REST Endpoints

**Autocomplete API:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/ajax/arsip/{keywords}` | Search arsip for autocomplete (max 10 results) |
| GET | `/ajax/user/{keywords}` | Search user for autocomplete (max 10 results) |

**Master Data AJAX:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/master/klas/create` | Create new klasifikasi |
| POST | `/master/klas/update` | Update klasifikasi |
| POST | `/master/klas/delete` | Delete klasifikasi |
| POST | `/master/klas/get` | Get single klasifikasi |
| GET | `/master/klas/reload` | Reload klasifikasi table |
| POST | `/master/penc/create` | Create pencipta |
| POST | `/master/penc/update` | Update pencipta |
| POST | `/master/penc/delete` | Delete pencipta |
| POST | `/master/penc/get` | Get single pencipta |
| GET | `/master/penc/reload` | Reload pencipta table |
| POST | `/master/pengolah/create` | Create unit pengolah |
| POST | `/master/pengolah/update` | Update unit pengolah |
| POST | `/master/pengolah/delete` | Delete unit pengolah |
| POST | `/master/pengolah/get` | Get single unit pengolah |
| GET | `/master/pengolah/reload` | Reload unit pengolah table |
| POST | `/master/lokasi/create` | Create lokasi |
| POST | `/master/lokasi/update` | Update lokasi |
| POST | `/master/lokasi/delete` | Delete lokasi |
| POST | `/master/lokasi/get` | Get single lokasi |
| GET | `/master/lokasi/reload` | Reload lokasi table |
| POST | `/master/media/create` | Create media |
| POST | `/master/media/update` | Update media |
| POST | `/master/media/delete` | Delete media |
| POST | `/master/media/get` | Get single media |
| GET | `/master/media/reload` | Reload media table |

**Sirkulasi API:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sirkulasi` | List sirkulasi with pagination |
| GET | `/sirkulasi/new` | Form new sirkulasi |
| POST | `/sirkulasi` | Create new sirkulasi |
| GET | `/sirkulasi/edit/{id}` | Edit sirkulasi |
| POST | `/sirkulasi/update/{id}` | Update sirkulasi |
| POST | `/sirkulasi/delete/{id}` | Delete sirkulasi |
| POST | `/sirkulasi/kembali/{id}` | Return arsip |

**Arsip API:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/arsip/new` | Form new arsip |
| POST | `/arsip` | Create new arsip |
| GET | `/arsip/edit/{id}` | Edit arsip |
| POST | `/arsip/update/{id}` | Update arsip |
| POST | `/arsip/delete/{id}` | Delete arsip |
| POST | `/arsip/delfile/{id}` | Delete arsip file |
| GET | `/view/{id}` | View arsip detail |
| GET | `/search` | Search arsip |

**Auth API:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/login` | Login page |
| POST | `/login` | Perform login |
| GET | `/logout` | Logout |

**User Management API:**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/user` | User list |
| POST | `/user` | Create user |
| POST | `/user/update` | Update user |
| POST | `/user/delete` | Delete user |
| POST | `/user/get` | Get single user |
| POST | `/user/cekUsername` | Check username availability |
| GET | `/user/reload` | Reload user table |

#### Response Format

**Success Response:**
```json
{
  "status": "success",
  "message": "Operation successful",
  "data": { }
}
```

**Error Response:**
```json
{
  "status": "error",
  "message": "Error message",
  "errors": { }
}
```

**Validation Error Response:**
```json
{
  "status": "error",
  "message": "Validasi gagal. Periksa kembali input Anda.",
  "errors": {
    "field_name": "Error message"
  }
}
```

## Kebutuhan Sistem

- PHP 8.2 atau lebih baru.
- Composer.
- Ekstensi PHP `intl` dan `mbstring`.
- Database yang didukung CodeIgniter 4, misalnya MySQL atau MariaDB.
- Node.js hanya dibutuhkan untuk menjalankan E2E test, bukan untuk menjalankan aplikasi produksi.

## Instalasi Lokal

1. Salin konfigurasi environment:

   ```bash
   cp env .env
   ```

2. Atur konfigurasi utama di `.env`, terutama `app.baseURL`, koneksi database, dan secret aplikasi.

3. Install dependency PHP:

   ```bash
   composer install
   ```

4. Jalankan migrasi dan seeder awal:

   ```bash
   php spark migrate
   php spark db:seed ArteriSeeder
   ```

5. Jalankan server lokal:

   ```bash
   php spark serve
   ```

   Secara default aplikasi dapat dibuka melalui `http://localhost:8080`.

## Menjalankan Test

Unit dan integration test PHP:

```bash
vendor/bin/phpunit --no-coverage --testdox
```

E2E test browser:

```bash
npm install
npm run test:e2e
```

E2E test menggunakan Playwright dan akan menjalankan server PHP lokal sesuai `playwright.config.ts`. Untuk mengganti alamat server:

```bash
E2E_BASE_URL=http://localhost:8081 npm run test:e2e
```

## Strategi File Test dan Release

File test tetap disimpan di repository publik karena penting untuk audit kualitas, keamanan, dan kontribusi. Namun file test tidak perlu ikut paket aplikasi yang diunduh pengguna akhir.

Strategi release yang disarankan:

- Branch utama menyimpan source lengkap, termasuk `tests/`, `e2e/`, konfigurasi PHPUnit, dan konfigurasi Playwright.
- CI menjalankan Composer audit, npm audit, PHPUnit, dan Playwright sebelum release.
- Artefak test seperti `build/`, `playwright-report/`, `test-results/`, dan `reports/` tidak dicommit.
- Paket release dibuat sebagai artifact terpisah dari GitHub Actions atau proses release lokal.
- Paket release hanya berisi file aplikasi yang dibutuhkan runtime, tanpa `tests/`, `e2e/`, report test, cache test, dependency dev, dan file environment lokal.
- Untuk deployment produksi berbasis Composer, gunakan:

  ```bash
  composer install --no-dev --optimize-autoloader
  ```

`.gitattributes` sudah menandai file dan folder development/test dengan `export-ignore`, sehingga arsip release berbasis `git archive` dapat dibuat lebih bersih.

## Catatan Produksi

- Arahkan document root web server ke folder `public/`, bukan root repository.
- Jangan commit `.env`, file upload pengguna, cache, session, log, atau report test.
- Gunakan HTTPS di deployment publik.
- Pastikan permission folder `writable/` hanya dibuka sesuai kebutuhan aplikasi.
- Jalankan migrasi database sebelum aplikasi digunakan.
- Buat backup database dan file upload secara rutin (lihat section Maintenance & Backup).

## Maintenance & Backup

### Database Backup

Command untuk backup database (otomatis compress .sql.gz):

```bash
php spark backup:database
```

Backup disimpan di `writable/backups/` dengan format `backup-YYYYMMDD-HHMMSS.sql.gz`. Default keep 7 backup terakhir (rotation otomatis).

Custom keep count:
```bash
php spark backup:database --keep=14
```

Offsite copy (mounted network drive, external storage):
```bash
php spark backup:database --offsite=/mnt/backup-storage
```

**Setup Cron untuk Daily Backup:**

Edit crontab (`crontab -e`):
```cron
# Backup database setiap hari jam 2 pagi, keep 14 backup, copy to network drive
0 2 * * * cd /path/to/arteri-ci4 && php spark backup:database --keep=14 --offsite=/mnt/backup-nas >> writable/logs/backup.log 2>&1
```

**Monitoring:**

Cek system_log table untuk monitor backup execution:
```sql
SELECT * FROM system_log WHERE aksi = 'DATABASE_BACKUP' ORDER BY tgl_transaksi DESC LIMIT 10;
```

**Restore dari Backup:**

```bash
# Ekstrak .gz
gunzip writable/backups/backup-20260611-020000.sql.gz

# Restore ke database
mysql -u username -p database_name < writable/backups/backup-20260611-020000.sql
```

### Trash Purge

Hapus permanen data soft-deleted yang lebih lama dari 30 hari:

```bash
php spark trash:purge
```

Setup cron mingguan:
```cron
# Purge trash setiap Minggu jam 3 pagi
0 3 * * 0 cd /path/to/arteri-ci4 && php spark trash:purge >> writable/logs/purge.log 2>&1
```

### Email Notifications

**Setup Email Configuration:**

Tambahkan ke `.env`:
```ini
email.fromEmail = noreply@example.com
email.fromName = Arteri System
email.protocol = smtp
email.SMTPHost = smtp.gmail.com
email.SMTPUser = your-email@gmail.com
email.SMTPPass = your-app-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

**Overdue Notifications:**

Send email ke peminjam untuk arsip yang melewati batas pengembalian:

```bash
php spark notify:overdue
php spark notify:overdue --dry-run  # Simulate tanpa send
```

Setup cron daily:
```cron
# Check overdue arsip setiap hari jam 9 pagi
0 9 * * * cd /path/to/arteri-ci4 && php spark notify:overdue >> writable/logs/notify.log 2>&1
```

**Note:** User harus punya field `email` di table `master_user` untuk menerima notifikasi.

## Pengembangan

### Testing

**Unit Tests (PHPUnit):**
```bash
composer test                    # Run all unit tests
php vendor/bin/phpunit tests/app/Controllers/Api/  # API tests only
```

**E2E Tests (Playwright):**

Requirements: Node.js 18+ and Playwright installed.

```bash
npm install                      # Install dependencies
npx playwright install          # Install browsers

# Run all E2E tests
npm run test:e2e

# Run specific test file
npx playwright test trash-recovery.spec.ts --project=chromium

# Run with UI mode (interactive)
npx playwright test --ui

# Generate report
npx playwright show-report
```

**E2E Test Coverage:**
- Authentication & access control
- CRUD operations (arsip, master data, users, sirkulasi)
- Search, filters, pagination, sorting
- File upload & import
- Dashboard analytics & charts
- Reports generation & export (Excel, print)
- Trash & recovery flow
- Security (OWASP, CSRF, XSS)
- Responsive design
- Edge cases

**Security Audit:**
```bash
npm run audit:security
```

Perintah yang umum dipakai:

```bash
composer test
npm run test:e2e
npm run audit:security
```

Untuk E2E yang lebih cepat selama pengembangan, jalankan satu project browser:

```bash
npx playwright test --project=chromium
```

## Lisensi

Arteri 2 mengikuti lisensi yang tercantum pada file `LICENSE`.
