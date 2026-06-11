import { test, expect } from '@playwright/test';
import * as path from 'path';

/**
 * E2E Demo: Full Application Flow
 *
 * Demonstrates complete usage of Arteri 2 from login to advanced features.
 * Screenshots are saved to e2e/screenshots/ for README documentation.
 */
test.describe('Arteri 2 - Full Application Demo', () => {
  const screenshotDir = 'e2e/screenshots/demo';
  let context: any;

  test.beforeAll(async ({ browser }) => {
    context = await browser.newContext({
      viewport: { width: 1920, height: 1080 },
    });
  });

  test.afterAll(async () => {
    await context.close();
  });

  test('Complete workflow: Login → Master Data → Upload Arsip → Sirkulasi → Reports → Dashboard', async () => {
    const page = await context.newPage();

    // ============================================================
    // 1. LOGIN & AUTHENTICATION
    // ============================================================
    await test.step('1. Login as Admin', async () => {
      await page.goto('/login');
      await page.screenshot({ path: `${screenshotDir}/01-login-page.png`, fullPage: true });

      await page.fill('input[name="username"]', 'admin');
      await page.fill('input[name="password"]', 'admin');
      await page.click('button[type="submit"]');

      await expect(page).toHaveURL('/');
      await page.screenshot({ path: `${screenshotDir}/02-home-after-login.png`, fullPage: true });
    });

    // ============================================================
    // 2. DASHBOARD OVERVIEW
    // ============================================================
    await test.step('2. View Dashboard Analytics', async () => {
      await page.goto('/dashboard');
      await page.waitForTimeout(2000); // Wait for charts to render

      await page.screenshot({ path: `${screenshotDir}/03-dashboard-overview.png`, fullPage: true });

      // Verify stats cards
      await expect(page.locator('.card').first()).toBeVisible();
    });

    // ============================================================
    // 3. MASTER DATA MANAGEMENT
    // ============================================================
    await test.step('3. Manage Master Data - Add Klasifikasi', async () => {
      await page.goto('/master/klas');
      await page.screenshot({ path: `${screenshotDir}/04-master-klasifikasi-list.png`, fullPage: true });

      // Add new klasifikasi
      await page.click('button:has-text("Tambah"), a:has-text("Tambah")');
      await page.waitForTimeout(500);

      const newKode = `DEMO.${Date.now()}`;
      await page.fill('input[name="kode"]', newKode);
      await page.fill('input[name="nama"]', 'Demo Klasifikasi Testing');
      await page.fill('input[name="retensi"]', '5');

      await page.screenshot({ path: `${screenshotDir}/05-master-add-klasifikasi-form.png` });

      await page.click('button:has-text("Simpan")');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/06-master-klasifikasi-added.png`, fullPage: true });
    });

    await test.step('4. Manage Master Data - Add Lokasi', async () => {
      await page.goto('/master/lokasi');

      await page.click('button:has-text("Tambah"), a:has-text("Tambah")');
      await page.waitForTimeout(500);

      await page.fill('input[name="nama_lokasi"]', `Ruang Demo ${Date.now()}`);
      await page.click('button:has-text("Simpan")');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/07-master-lokasi-added.png`, fullPage: true });
    });

    // ============================================================
    // 5. CREATE NEW ARSIP WITH FILE UPLOAD
    // ============================================================
    await test.step('5. Create New Arsip with File Upload', async () => {
      await page.goto('/arsip/new');
      await page.screenshot({ path: `${screenshotDir}/08-arsip-form-empty.png`, fullPage: true });

      // Fill arsip form
      const testNoarsip = `DEMO-${Date.now()}`;
      await page.fill('input[name="noarsip"]', testNoarsip);
      await page.fill('input[name="judul"]', 'Dokumen Demo Arteri 2 - Complete Flow Testing');

      // Select master data
      await page.selectOption('select[name="kode"]', { index: 1 });
      await page.selectOption('select[name="pencipta"]', { index: 1 });
      await page.selectOption('select[name="pengolah"]', { index: 1 });
      await page.selectOption('select[name="lokasi"]', { index: 1 });
      await page.selectOption('select[name="media"]', { index: 1 });

      await page.fill('input[name="tanggal"]', '2026-06-01');
      await page.fill('textarea[name="uraian"]', 'Ini adalah dokumen demo untuk menampilkan fitur lengkap Arteri 2. Mencakup pengelolaan arsip, upload file, sirkulasi peminjaman, dan pelaporan.');
      await page.fill('input[name="ket"]', 'asli');
      await page.fill('input[name="jumlah"]', '1');
      await page.fill('input[name="nobox"]', 'BOX-DEMO-01');

      await page.screenshot({ path: `${screenshotDir}/09-arsip-form-filled.png`, fullPage: true });

      // Upload file (create dummy file)
      const dummyFilePath = path.join(process.cwd(), 'tests/_support/fixtures/demo-document.pdf');

      // If file input exists, upload
      const fileInput = page.locator('input[type="file"]');
      if (await fileInput.isVisible()) {
        // For demo, we'll just screenshot the form
        await page.screenshot({ path: `${screenshotDir}/10-arsip-ready-to-submit.png`, fullPage: true });
      }

      // Submit
      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);

      await expect(page.locator('.alert-success, .alert')).toBeVisible({ timeout: 5000 });
      await page.screenshot({ path: `${screenshotDir}/11-arsip-created-success.png`, fullPage: true });
    });

    // ============================================================
    // 6. SEARCH & FILTER ARSIP
    // ============================================================
    await test.step('6. Search and Filter Arsip', async () => {
      await page.goto('/search');
      await page.screenshot({ path: `${screenshotDir}/12-search-page.png`, fullPage: true });

      // Search by keyword
      await page.fill('input[name="katakunci"]', 'DEMO');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/13-search-results.png`, fullPage: true });

      // Advanced filter
      await page.selectOption('select[name="kode"]', { index: 1 });
      await page.fill('input[name="tanggal_awal"]', '2026-01-01');
      await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/14-search-filtered.png`, fullPage: true });
    });

    // ============================================================
    // 7. EDIT ARSIP
    // ============================================================
    await test.step('7. Edit Existing Arsip', async () => {
      // Click edit button on first result
      const editButton = page.locator('a:has-text("Edit"), .btn-warning').first();

      if (await editButton.isVisible()) {
        await editButton.click();
        await page.waitForTimeout(500);

        await page.screenshot({ path: `${screenshotDir}/15-arsip-edit-form.png`, fullPage: true });

        // Update uraian
        await page.fill('textarea[name="uraian"]', 'Uraian telah diperbarui melalui fitur edit. Arteri 2 mendukung full CRUD operations.');

        await page.screenshot({ path: `${screenshotDir}/16-arsip-edit-updated.png`, fullPage: true });

        await page.click('button[type="submit"]');
        await page.waitForTimeout(1000);

        await page.screenshot({ path: `${screenshotDir}/17-arsip-edit-success.png`, fullPage: true });
      }
    });

    // ============================================================
    // 8. SIRKULASI - PEMINJAMAN ARSIP
    // ============================================================
    await test.step('8. Create Sirkulasi - Borrow Arsip', async () => {
      await page.goto('/sirkulasi/new');
      await page.screenshot({ path: `${screenshotDir}/18-sirkulasi-form.png`, fullPage: true });

      // Autocomplete arsip (type dan pilih)
      await page.fill('input[name="noarsip"]', 'DEMO');
      await page.waitForTimeout(1000); // Wait for autocomplete

      await page.screenshot({ path: `${screenshotDir}/19-sirkulasi-autocomplete.png` });

      // Click first autocomplete result jika ada
      const autocompleteItem = page.locator('.autocomplete-item, .ui-menu-item').first();
      if (await autocompleteItem.isVisible({ timeout: 2000 }).catch(() => false)) {
        await autocompleteItem.click();
      } else {
        // Fallback: type noarsip manually
        await page.fill('input[name="noarsip"]', 'DEMO-1234');
      }

      // Select user
      await page.fill('input[name="username_peminjam"]', 'user');
      await page.waitForTimeout(500);

      // Set return date (7 days from now)
      const returnDate = new Date();
      returnDate.setDate(returnDate.getDate() + 7);
      await page.fill('input[name="tgl_haruskembali"]', returnDate.toISOString().split('T')[0]);

      await page.screenshot({ path: `${screenshotDir}/20-sirkulasi-form-filled.png`, fullPage: true });

      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/21-sirkulasi-created.png`, fullPage: true });
    });

    await test.step('9. View Sirkulasi List', async () => {
      await page.goto('/sirkulasi');
      await page.screenshot({ path: `${screenshotDir}/22-sirkulasi-list.png`, fullPage: true });

      // Filter status
      const statusFilter = page.locator('select[name="status"]');
      if (await statusFilter.isVisible()) {
        await statusFilter.selectOption('dipinjam');
        await page.click('button[type="submit"]');
        await page.waitForTimeout(1000);

        await page.screenshot({ path: `${screenshotDir}/23-sirkulasi-filtered.png`, fullPage: true });
      }
    });

    // ============================================================
    // 10. REPORTS GENERATION
    // ============================================================
    await test.step('10. Generate Arsip Report', async () => {
      await page.goto('/report/arsip');
      await page.screenshot({ path: `${screenshotDir}/24-report-arsip-form.png`, fullPage: true });

      await page.fill('input[name="tanggal_awal"]', '2026-01-01');
      await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/25-report-arsip-results.png`, fullPage: true });
    });

    await test.step('11. Generate Sirkulasi Report', async () => {
      await page.goto('/report/sirkulasi');

      await page.fill('input[name="tanggal_awal"]', '2026-01-01');
      await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);

      await page.screenshot({ path: `${screenshotDir}/26-report-sirkulasi-results.png`, fullPage: true });
    });

    // ============================================================
    // 12. IMPORT DATA
    // ============================================================
    await test.step('12. Import Data Feature', async () => {
      await page.goto('/import');
      await page.screenshot({ path: `${screenshotDir}/27-import-page.png`, fullPage: true });

      // Screenshot menunjukkan drag-drop zone dan template download
    });

    // ============================================================
    // 13. USER MANAGEMENT
    // ============================================================
    await test.step('13. User Management', async () => {
      await page.goto('/user');
      await page.screenshot({ path: `${screenshotDir}/28-user-management.png`, fullPage: true });
    });

    // ============================================================
    // 14. AUDIT LOG
    // ============================================================
    await test.step('14. View Audit Log', async () => {
      await page.goto('/audit');
      await page.screenshot({ path: `${screenshotDir}/29-audit-log.png`, fullPage: true });
    });

    // ============================================================
    // 15. TRASH & RECOVERY
    // ============================================================
    await test.step('15. Trash Management', async () => {
      await page.goto('/trash');
      await page.screenshot({ path: `${screenshotDir}/30-trash-recovery.png`, fullPage: true });
    });

    // ============================================================
    // 16. RESPONSIVE MOBILE VIEW
    // ============================================================
    await test.step('16. Mobile Responsive View', async () => {
      await page.setViewportSize({ width: 375, height: 812 });

      await page.goto('/dashboard');
      await page.waitForTimeout(1000);
      await page.screenshot({ path: `${screenshotDir}/31-mobile-dashboard.png`, fullPage: true });

      await page.goto('/search');
      await page.screenshot({ path: `${screenshotDir}/32-mobile-search.png`, fullPage: true });
    });

    // ============================================================
    // 17. FINAL DASHBOARD OVERVIEW
    // ============================================================
    await test.step('17. Final Dashboard with All Data', async () => {
      await page.setViewportSize({ width: 1920, height: 1080 });
      await page.goto('/dashboard');
      await page.waitForTimeout(2000);

      await page.screenshot({ path: `${screenshotDir}/33-final-dashboard-complete.png`, fullPage: true });
    });

    await page.close();
  });
});
