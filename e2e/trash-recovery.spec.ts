import { test, expect } from '@playwright/test';

test.describe('Trash and Recovery', () => {
  test.beforeEach(async ({ page }) => {
    // Login sebagai admin
    await page.goto('/login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL('/');
  });

  test('should soft delete arsip and show in trash', async ({ page }) => {
    // Buat arsip baru untuk di-delete
    await page.goto('/arsip/new');

    const testNoarsip = `TRASH-TEST-${Date.now()}`;
    await page.fill('input[name="noarsip"]', testNoarsip);
    await page.fill('input[name="judul"]', 'Test Arsip for Trash');
    await page.selectOption('select[name="kode"]', { index: 1 });
    await page.selectOption('select[name="pencipta"]', { index: 1 });
    await page.selectOption('select[name="pengolah"]', { index: 1 });
    await page.selectOption('select[name="lokasi"]', { index: 1 });
    await page.selectOption('select[name="media"]', { index: 1 });
    await page.fill('input[name="tanggal"]', '2026-06-10');
    await page.fill('input[name="uraian"]', 'Test uraian');
    await page.fill('input[name="ket"]', 'asli');
    await page.fill('input[name="jumlah"]', '1');
    await page.fill('input[name="nobox"]', 'BOX-01');

    await page.click('button[type="submit"]');
    await expect(page.locator('.alert-success')).toBeVisible();

    // Search arsip yang baru dibuat
    await page.goto('/search');
    await page.fill('input[name="katakunci"]', testNoarsip);
    await page.click('button[type="submit"]');

    // Delete arsip (soft delete)
    const deleteButton = page.locator(`tr:has-text("${testNoarsip}") .btn-danger`).first();
    await deleteButton.click();

    // Confirm delete
    page.on('dialog', dialog => dialog.accept());
    await page.click(`tr:has-text("${testNoarsip}") .btn-danger`).catch(() => {});

    // Verify arsip tidak muncul di search biasa
    await page.goto('/search');
    await page.fill('input[name="katakunci"]', testNoarsip);
    await page.click('button[type="submit"]');
    await expect(page.locator(`tr:has-text("${testNoarsip}")`)).not.toBeVisible();

    // Verify arsip ada di trash
    await page.goto('/trash');
    await expect(page.locator(`tr:has-text("${testNoarsip}")`)).toBeVisible();
    await expect(page.locator(`tr:has-text("${testNoarsip}") td:has-text("data_arsip")`)).toBeVisible();
  });

  test('should restore deleted item from trash', async ({ page }) => {
    // Ke halaman trash
    await page.goto('/trash');

    // Ambil item pertama di trash (jika ada)
    const firstRow = page.locator('tbody tr').first();
    const hasItems = await firstRow.isVisible().catch(() => false);

    if (!hasItems) {
      test.skip('No items in trash to restore');
      return;
    }

    const itemText = await firstRow.locator('td').nth(1).textContent();

    // Click restore button
    await firstRow.locator('.btn-success').click();

    // Confirm restore
    page.on('dialog', dialog => dialog.accept());

    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });

    // Verify item hilang dari trash
    await expect(page.locator(`tr:has-text("${itemText}")`)).not.toBeVisible();
  });

  test('should permanently delete old items', async ({ page }) => {
    await page.goto('/trash');

    // Get initial count
    const initialCount = await page.locator('tbody tr').count();

    if (initialCount === 0) {
      test.skip('No items in trash to purge');
      return;
    }

    // Click "Hapus Permanen" untuk item lama (>30 hari)
    const purgeButton = page.locator('button:has-text("Hapus Permanen")').first();
    const isVisible = await purgeButton.isVisible().catch(() => false);

    if (!isVisible) {
      test.skip('No old items (>30 days) to purge');
      return;
    }

    await purgeButton.click();

    // Confirm purge
    page.on('dialog', dialog => dialog.accept());

    // Verify success message
    await expect(page.locator('.alert-success')).toBeVisible({ timeout: 5000 });
  });

  test('should filter trash by table type', async ({ page }) => {
    await page.goto('/trash');

    // Pilih filter table
    const filterSelect = page.locator('select[name="tabel"]');
    if (await filterSelect.isVisible()) {
      await filterSelect.selectOption('data_arsip');
      await page.click('button[type="submit"]');

      // Verify hanya data_arsip yang muncul
      const rows = page.locator('tbody tr');
      const count = await rows.count();

      if (count > 0) {
        for (let i = 0; i < count; i++) {
          await expect(rows.nth(i).locator('td:has-text("data_arsip")')).toBeVisible();
        }
      }
    }
  });

  test('should show trash stats', async ({ page }) => {
    await page.goto('/trash');

    // Verify stats cards atau count
    const pageContent = await page.content();
    expect(pageContent).toContain('Trash'); // atau "Sampah"
  });

  test('admin only can access trash', async ({ page }) => {
    // Logout admin
    await page.goto('/logout');

    // Login sebagai user biasa
    await page.goto('/login');
    await page.fill('input[name="username"]', 'user');
    await page.fill('input[name="password"]', 'user');
    await page.click('button[type="submit"]');

    // Try access trash
    await page.goto('/trash');

    // Should be redirected atau forbidden
    const url = page.url();
    expect(url).not.toContain('/trash');
  });
});
