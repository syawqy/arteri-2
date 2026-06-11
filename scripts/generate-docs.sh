#!/bin/bash

# Generate Feature Documentation from E2E Screenshots
# Run after executing full-demo-flow.spec.ts

SCREENSHOT_DIR="e2e/screenshots/demo"
OUTPUT_FILE="FEATURES.md"

cat > "$OUTPUT_FILE" << 'EOF'
# Arteri 2 - Feature Documentation

Dokumentasi lengkap fitur-fitur Arteri 2 dengan screenshot dari E2E testing.

---

## 1. Authentication & Security

### Login
![Login Page](e2e/screenshots/demo/01-login-page.png)

Sistem autentikasi dengan:
- Username dan password
- CSRF protection
- Session management
- Role-based access control (admin/user)

---

## 2. Dashboard Analytics

### Dashboard Overview
![Dashboard](e2e/screenshots/demo/03-dashboard-overview.png)

Dashboard menampilkan:
- **Statistics Cards**: Total arsip, arsip dipinjam, overdue, aktivitas
- **Charts & Visualizations**:
  - Distribusi per klasifikasi
  - Aktivitas bulanan
  - Distribusi per lokasi
  - Distribusi per media
  - Distribusi per pencipta
- **Skeleton loaders** saat data loading
- **Responsive design** untuk mobile

![Final Dashboard](e2e/screenshots/demo/33-final-dashboard-complete.png)

---

## 3. Master Data Management

### Klasifikasi Kode
![Master Klasifikasi List](e2e/screenshots/demo/04-master-klasifikasi-list.png)

![Add Klasifikasi Form](e2e/screenshots/demo/05-master-add-klasifikasi-form.png)

### Lokasi Penyimpanan
![Master Lokasi](e2e/screenshots/demo/07-master-lokasi-added.png)

Fitur Master Data:
- **CRUD operations** untuk semua master data
- **Inline editing** untuk quick update
- **Soft delete** dengan trash recovery
- **Caching** untuk performa optimal
- Master data types:
  - Klasifikasi Kode
  - Pencipta
  - Unit Pengolah
  - Lokasi Penyimpanan
  - Media Arsip

---

## 4. Arsip Management

### Create New Arsip
![Arsip Form Empty](e2e/screenshots/demo/08-arsip-form-empty.png)

![Arsip Form Filled](e2e/screenshots/demo/09-arsip-form-filled.png)

![Arsip Created](e2e/screenshots/demo/11-arsip-created-success.png)

### Edit Arsip
![Edit Arsip Form](e2e/screenshots/demo/15-arsip-edit-form.png)

![Edit Success](e2e/screenshots/demo/17-arsip-edit-success.png)

Fitur Arsip:
- **Full CRUD operations**
- **File upload** dengan drag & drop
- **Metadata lengkap**: noarsip, judul, klasifikasi, pencipta, pengolah, lokasi, media, tanggal, uraian, keterangan
- **Validasi input** otomatis
- **Audit trail** untuk semua perubahan

---

## 5. Search & Filter

### Search Page
![Search Page](e2e/screenshots/demo/12-search-page.png)

![Search Results](e2e/screenshots/demo/13-search-results.png)

![Filtered Results](e2e/screenshots/demo/14-search-filtered.png)

Fitur Pencarian:
- **Keyword search** di semua field
- **Advanced filters**:
  - Klasifikasi kode
  - Range tanggal
  - Pencipta
  - Lokasi
- **Cursor-based pagination** untuk performa optimal
- **Sorting** by berbagai field
- **Export results** ke Excel

---

## 6. Sirkulasi (Peminjaman)

### Create Peminjaman
![Sirkulasi Form](e2e/screenshots/demo/18-sirkulasi-form.png)

![Autocomplete Arsip](e2e/screenshots/demo/19-sirkulasi-autocomplete.png)

![Sirkulasi Filled](e2e/screenshots/demo/20-sirkulasi-form-filled.png)

![Sirkulasi Created](e2e/screenshots/demo/21-sirkulasi-created.png)

### Sirkulasi List
![Sirkulasi List](e2e/screenshots/demo/22-sirkulasi-list.png)

![Sirkulasi Filtered](e2e/screenshots/demo/23-sirkulasi-filtered.png)

Fitur Sirkulasi:
- **Autocomplete** untuk pencarian arsip cepat
- **Autocomplete** untuk username peminjam
- **Set tanggal harus kembali**
- **Track status**: dipinjam, dikembalikan, overdue
- **Email notifications** untuk overdue arsip
- **Pencatatan tanggal pengembalian**
- **Filter & search** peminjaman

---

## 7. Reports & Export

### Arsip Report
![Report Arsip Form](e2e/screenshots/demo/24-report-arsip-form.png)

![Report Arsip Results](e2e/screenshots/demo/25-report-arsip-results.png)

### Sirkulasi Report
![Report Sirkulasi Results](e2e/screenshots/demo/26-report-sirkulasi-results.png)

Fitur Reporting:
- **Laporan Arsip** dengan filter tanggal & klasifikasi
- **Laporan Sirkulasi** dengan filter tanggal & status
- **Export ke Excel** (.xlsx)
- **Print preview** untuk cetak fisik
- **Summary statistics** di setiap laporan

---

## 8. Import Data

### Import Page
![Import Page](e2e/screenshots/demo/27-import-page.png)

Fitur Import:
- **Drag & drop** Excel file
- **Template download** untuk format yang benar
- **Progress indicator** saat upload
- **Validation** dan error reporting
- **Bulk insert** untuk efficiency

---

## 9. User Management

### User Management
![User Management](e2e/screenshots/demo/28-user-management.png)

Fitur User Management:
- **CRUD users** (admin only)
- **Role assignment**: admin, user
- **Access control** per modul
- **Klasifikasi access** restriction per user
- **Password hashing** dengan bcrypt

---

## 10. Audit Log

### Audit Log
![Audit Log](e2e/screenshots/demo/29-audit-log.png)

Fitur Audit:
- **Track semua aktivitas** CRUD
- **Log detail**: username, tanggal, aksi, tabel, record ID, IP address
- **Searchable & filterable**
- **Retention policy** configurable
- **Compliance ready** untuk audit trail

---

## 11. Trash & Recovery

### Trash Management
![Trash Recovery](e2e/screenshots/demo/30-trash-recovery.png)

Fitur Trash:
- **Soft delete** untuk semua data
- **30 hari recovery period** (configurable)
- **Restore functionality** untuk undelete
- **Permanent delete** setelah recovery period
- **Trash purge command** untuk cleanup
- **Filter by table type**

---

## 12. Mobile Responsive

### Mobile Dashboard
![Mobile Dashboard](e2e/screenshots/demo/31-mobile-dashboard.png)

### Mobile Search
![Mobile Search](e2e/screenshots/demo/32-mobile-search.png)

Fitur Responsive:
- **Touch-friendly** interfaces
- **Responsive tables** dengan horizontal scroll
- **Collapsible navigation** untuk mobile
- **Optimized forms** untuk touch input
- **Chart adaptations** untuk small screens

---

## REST API (Bonus)

Arteri 2 juga menyediakan REST API lengkap:

### API Features
- **API Key authentication** dengan rate limiting
- **Full CRUD endpoints** untuk arsip, sirkulasi, master data
- **Cursor-based pagination**
- **OpenAPI/Swagger documentation** di `/api/v1/docs`
- **JSON responses** dengan standard format
- **Error handling** dengan HTTP status codes yang tepat

### API Documentation
Access Swagger UI: `http://your-domain/api/v1/docs`

---

## Backup & Maintenance

### Automated Backup
```bash
# Daily database backup dengan rotation
php spark backup:database --keep=14 --offsite=/mnt/backup-nas
```

### Email Notifications
```bash
# Check overdue arsip dan send email
php spark notify:overdue
```

### Trash Cleanup
```bash
# Purge data >30 hari dari trash
php spark trash:purge
```

---

## Security Features

- **CSRF Protection** di semua forms
- **XSS Prevention** dengan output escaping
- **SQL Injection Prevention** dengan prepared statements
- **Authentication & Authorization** per modul
- **Password hashing** dengan bcrypt
- **Rate limiting** untuk API
- **Audit logging** untuk compliance
- **HTTPS ready** untuk production

---

## Performance Optimizations

- **Master data caching** (Redis/File cache)
- **Database indexing** pada kolom penting
- **Cursor-based pagination** untuk large datasets
- **Lazy loading** charts dan images
- **Asset minification** untuk production
- **Query optimization** dengan proper joins

---

## Technology Stack

- **Backend**: CodeIgniter 4 (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Frontend**: Bootstrap 5, jQuery, Chart.js
- **Testing**: PHPUnit (unit), Playwright (E2E)
- **CI/CD**: GitHub Actions
- **Documentation**: OpenAPI 3.0

---

## Getting Started

1. **Setup**: `composer install && npm install`
2. **Configure**: Copy `.env.example` to `.env` dan sesuaikan
3. **Migrate**: `php spark migrate`
4. **Seed**: `php spark db:seed ArteriSeeder`
5. **Run**: `php spark serve`
6. **Login**: Username `admin`, Password `admin`

---

**Arteri 2** - Sistem Manajemen Arsip Digital yang Modern, Aman, dan Mudah Digunakan.

EOF

echo "✓ Feature documentation generated: $OUTPUT_FILE"
echo ""
echo "To generate screenshots, run:"
echo "  npx playwright test full-demo-flow.spec.ts --project=chromium"
echo ""
echo "Note: Requires Node.js 18+ and running application on localhost"
