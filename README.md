# Arteri 2

Arteri 2 adalah aplikasi pengelola arsip digital berbasis CodeIgniter 4 untuk pencatatan, pencarian, pengelolaan, peminjaman, audit, impor, ekspor, dan pengamanan akses arsip.

Proyek ini adalah kelanjutan dari [Arteri](https://github.com/dicarve/arteri), dengan target runtime PHP yang lebih baru, basis aplikasi yang lebih stabil, dan praktik keamanan yang lebih kuat untuk penggunaan publik.

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
- Buat backup database dan file upload secara rutin.

## Pengembangan

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
