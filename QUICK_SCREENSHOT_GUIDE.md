# Quick Screenshot Guide - Manual Method

Karena Node.js 16 belum support Playwright, gunakan metode manual untuk generate screenshots.

## Setup

1. **Start aplikasi**
   ```bash
   php spark serve --port=8080
   ```

2. **Buka browser** (Chrome/Edge recommended)
   - URL: `http://localhost:8080`

## Cara Screenshot di Browser

### Chrome DevTools Method (Recommended)
1. Tekan `F12` untuk buka DevTools
2. Tekan `Ctrl+Shift+P` (Command Palette)
3. Ketik "screenshot"
4. Pilih "Capture full size screenshot"
5. File otomatis download

### Manual Method
- Tekan `Windows+Shift+S` (Windows Snipping Tool)
- Atau gunakan extension browser screenshot

## Screenshot Checklist

Buat folder: `e2e/screenshots/demo/`

### 1. Authentication (2 screenshots)
- [ ] `01-login-page.png` - Buka `/login`, screenshot form login
- [ ] `02-home-after-login.png` - Setelah login, screenshot homepage

### 2. Dashboard (2 screenshots)
- [ ] `03-dashboard-overview.png` - Buka `/dashboard`, tunggu charts load (2 detik)
- [ ] `33-final-dashboard-complete.png` - Dashboard setelah ada data (screenshot terakhir)

### 3. Master Data (3 screenshots)
- [ ] `04-master-klasifikasi-list.png` - Buka `/master/klas`
- [ ] `05-master-add-klasifikasi-form.png` - Klik "Tambah Kode", screenshot modal/form
- [ ] `07-master-lokasi-added.png` - Buka `/master/lokasi`, screenshot table

### 4. Arsip Management (6 screenshots)
- [ ] `08-arsip-form-empty.png` - Buka `/arsip/new`
- [ ] `09-arsip-form-filled.png` - Isi semua field (jangan submit)
- [ ] `11-arsip-created-success.png` - Setelah submit, screenshot alert success
- [ ] `15-arsip-edit-form.png` - Klik edit di list, screenshot form edit
- [ ] `16-arsip-edit-updated.png` - Ubah field, screenshot sebelum submit
- [ ] `17-arsip-edit-success.png` - Setelah submit edit

### 5. Search & Filter (3 screenshots)
- [ ] `12-search-page.png` - Buka `/search`
- [ ] `13-search-results.png` - Isi keyword "test", submit, screenshot hasil
- [ ] `14-search-filtered.png` - Pilih filter klasifikasi, tanggal, submit

### 6. Sirkulasi (6 screenshots)
- [ ] `18-sirkulasi-form.png` - Buka `/sirkulasi/new`
- [ ] `19-sirkulasi-autocomplete.png` - Ketik di field noarsip, tunggu autocomplete muncul
- [ ] `20-sirkulasi-form-filled.png` - Isi semua field
- [ ] `21-sirkulasi-created.png` - Submit, screenshot success
- [ ] `22-sirkulasi-list.png` - Buka `/sirkulasi`
- [ ] `23-sirkulasi-filtered.png` - Filter by status, screenshot hasil

### 7. Reports (3 screenshots)
- [ ] `24-report-arsip-form.png` - Buka `/report/arsip`
- [ ] `25-report-arsip-results.png` - Isi filter tanggal, submit, screenshot table
- [ ] `26-report-sirkulasi-results.png` - Buka `/report/sirkulasi`, submit filter

### 8. Admin Features (3 screenshots)
- [ ] `27-import-page.png` - Buka `/import`
- [ ] `28-user-management.png` - Buka `/user`
- [ ] `29-audit-log.png` - Buka `/audit`

### 9. Trash (1 screenshot)
- [ ] `30-trash-recovery.png` - Buka `/trash`

### 10. Mobile Responsive (2 screenshots)
- [ ] `31-mobile-dashboard.png` - F12 → Toggle device (375x812), `/dashboard`
- [ ] `32-mobile-search.png` - Still mobile view, `/search`

## After Screenshots Complete

1. **Verify semua 33 files ada** di `e2e/screenshots/demo/`

2. **Generate FEATURES.md**
   ```bash
   bash scripts/generate-docs.sh
   ```

3. **Commit screenshots**
   ```bash
   git add e2e/screenshots/demo/*.png FEATURES.md
   git commit -m "docs: Add application screenshots for feature documentation"
   git push origin main
   ```

## Tips

- **Resolution**: 1920x1080 untuk desktop screenshots
- **Mobile**: 375x812 (iPhone X size)
- **Wait**: Tunggu animasi/loading selesai sebelum screenshot
- **Clean**: Gunakan data test yang rapi (tidak error/kosong)
- **Consistent**: Gunakan user "admin" untuk semua screenshots

## Estimasi Waktu

- Setup: 5 menit
- Screenshots: 20-30 menit (33 files)
- Generate docs: 1 menit
- Total: ~30-40 menit

## Alternative: Quick Demo (10 screenshots)

Kalau mau cepat, ambil yang penting saja:

1. Login (01)
2. Dashboard (03)
3. Master data (04)
4. Arsip form (09)
5. Search results (13)
6. Sirkulasi form (20)
7. Report results (25)
8. User management (28)
9. Trash (30)
10. Mobile dashboard (31)

Ini sudah cukup untuk menunjukkan core features.
