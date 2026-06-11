# Cara Generate Screenshots untuk Dokumentasi

## Prerequisites

1. **Upgrade Node.js ke 18+**
   ```bash
   # Check current version
   node --version
   
   # Install Node.js 18 LTS
   # Windows: Download dari https://nodejs.org/
   # Linux: nvm install 18 && nvm use 18
   ```

2. **Install Dependencies**
   ```bash
   npm install
   npx playwright install chromium
   ```

3. **Start Application**
   ```bash
   # Terminal 1: Start PHP server
   php spark serve --port=8080
   
   # Terminal 2: Biarkan running
   ```

## Generate Screenshots

```bash
# Run full demo test (akan generate 33 screenshots)
npx playwright test full-demo-flow.spec.ts --project=chromium

# Screenshots akan tersimpan di:
# e2e/screenshots/demo/01-login-page.png
# e2e/screenshots/demo/02-home-after-login.png
# ... (sampai 33 files)
```

## Generate Documentation

```bash
# Setelah screenshots ada, generate FEATURES.md
./scripts/generate-docs.sh

# Atau manual:
bash scripts/generate-docs.sh
```

## Hasil

- **e2e/screenshots/demo/** - 33 screenshot files
- **FEATURES.md** - Documentation dengan embedded screenshots
- **README.md** - Updated dengan link ke FEATURES.md

## Alternative: Manual Screenshots

Jika tidak bisa run E2E test, bisa screenshot manual:

1. Login ke aplikasi
2. Navigate ke setiap halaman
3. Ambil screenshot (F12 → Ctrl+Shift+P → "Capture screenshot")
4. Save dengan naming sesuai list di full-demo-flow.spec.ts
5. Letakkan di `e2e/screenshots/demo/`

## Screenshot List

```
01-login-page.png - Login form
02-home-after-login.png - Homepage setelah login
03-dashboard-overview.png - Dashboard dengan charts
04-master-klasifikasi-list.png - Master data list
05-master-add-klasifikasi-form.png - Form tambah klasifikasi
06-master-klasifikasi-added.png - Success tambah
07-master-lokasi-added.png - Master lokasi
08-arsip-form-empty.png - Form arsip kosong
09-arsip-form-filled.png - Form arsip terisi
10-arsip-ready-to-submit.png - Siap submit
11-arsip-created-success.png - Success message
12-search-page.png - Halaman search
13-search-results.png - Hasil search
14-search-filtered.png - Hasil filter
15-arsip-edit-form.png - Form edit arsip
16-arsip-edit-updated.png - Form updated
17-arsip-edit-success.png - Edit success
18-sirkulasi-form.png - Form sirkulasi
19-sirkulasi-autocomplete.png - Autocomplete aktif
20-sirkulasi-form-filled.png - Form terisi
21-sirkulasi-created.png - Sirkulasi created
22-sirkulasi-list.png - List sirkulasi
23-sirkulasi-filtered.png - Filtered list
24-report-arsip-form.png - Form laporan arsip
25-report-arsip-results.png - Hasil laporan
26-report-sirkulasi-results.png - Laporan sirkulasi
27-import-page.png - Halaman import
28-user-management.png - User management
29-audit-log.png - Audit log
30-trash-recovery.png - Trash/sampah
31-mobile-dashboard.png - Mobile view dashboard
32-mobile-search.png - Mobile view search
33-final-dashboard-complete.png - Dashboard final
```

