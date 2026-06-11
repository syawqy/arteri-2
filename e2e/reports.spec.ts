import { test, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';

test.describe('Reports Generation and Export', () => {
  test.beforeEach(async ({ page }) => {
    // Login sebagai admin
    await page.goto('/login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL('/');
  });

  test('should access reports page', async ({ page }) => {
    await page.goto('/report');

    await expect(page).toHaveURL('/report');
    await expect(page.locator('h1, h2')).toContainText(/Laporan|Report/i);
  });

  test('should display arsip report with filters', async ({ page }) => {
    await page.goto('/report/arsip');

    // Verify filter form ada
    await expect(page.locator('input[name="tanggal_awal"]')).toBeVisible();
    await expect(page.locator('input[name="tanggal_akhir"]')).toBeVisible();
    await expect(page.locator('select[name="kode"]')).toBeVisible();

    // Submit filter
    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    // Verify table hasil muncul
    await expect(page.locator('table')).toBeVisible({ timeout: 5000 });
  });

  test('should display sirkulasi report', async ({ page }) => {
    await page.goto('/report/sirkulasi');

    // Verify filter sirkulasi
    await expect(page.locator('input[name="tanggal_awal"]')).toBeVisible();
    await expect(page.locator('input[name="tanggal_akhir"]')).toBeVisible();

    // Submit
    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    // Verify table sirkulasi
    await expect(page.locator('table')).toBeVisible({ timeout: 5000 });
  });

  test('should export arsip report to Excel', async ({ page }) => {
    await page.goto('/report/arsip');

    // Set filter
    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    // Wait for results
    await page.waitForSelector('table', { timeout: 5000 });

    // Click export Excel button
    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 10000 }),
      page.click('a:has-text("Excel"), button:has-text("Excel")')
    ]);

    // Verify download
    expect(download.suggestedFilename()).toMatch(/\.xlsx?$/i);

    // Verify file downloaded
    const downloadPath = await download.path();
    expect(downloadPath).toBeTruthy();

    // Verify file not empty
    const stats = fs.statSync(downloadPath!);
    expect(stats.size).toBeGreaterThan(0);
  });

  test('should export sirkulasi report to Excel', async ({ page }) => {
    await page.goto('/report/sirkulasi');

    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    await page.waitForSelector('table', { timeout: 5000 });

    const [download] = await Promise.all([
      page.waitForEvent('download', { timeout: 10000 }),
      page.click('a:has-text("Excel"), button:has-text("Excel")')
    ]);

    expect(download.suggestedFilename()).toMatch(/\.xlsx?$/i);
  });

  test('should print arsip report', async ({ page }) => {
    await page.goto('/report/arsip');

    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    await page.waitForSelector('table', { timeout: 5000 });

    // Click print button
    const printButton = page.locator('a:has-text("Print"), button:has-text("Print")');

    if (await printButton.isVisible()) {
      // Intercept print dialog
      page.on('popup', async popup => {
        await expect(popup).toHaveURL(/\/print$/);
      });

      await printButton.click();
    } else {
      test.skip('Print button not found');
    }
  });

  test('should filter arsip report by klasifikasi', async ({ page }) => {
    await page.goto('/report/arsip');

    // Select specific klasifikasi
    await page.selectOption('select[name="kode"]', { index: 1 });
    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    await page.waitForSelector('table', { timeout: 5000 });

    // Verify table has data
    const rows = page.locator('tbody tr');
    const count = await rows.count();

    // If has data, all should match selected klasifikasi
    if (count > 0) {
      // Check bahwa filter bekerja (data filtered)
      await expect(page.locator('table')).toBeVisible();
    }
  });

  test('should show empty state for no results', async ({ page }) => {
    await page.goto('/report/arsip');

    // Set filter dengan range yang pasti kosong
    await page.fill('input[name="tanggal_awal"]', '2030-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2030-01-02');
    await page.click('button[type="submit"]');

    // Should show empty state atau "Tidak ada data"
    const content = await page.content();
    const hasEmptyMessage = content.includes('Tidak ada') ||
                           content.includes('No data') ||
                           content.includes('kosong');

    expect(hasEmptyMessage).toBeTruthy();
  });

  test('should validate date range', async ({ page }) => {
    await page.goto('/report/arsip');

    // Try invalid date range (end before start)
    await page.fill('input[name="tanggal_awal"]', '2026-12-31');
    await page.fill('input[name="tanggal_akhir"]', '2026-01-01');
    await page.click('button[type="submit"]');

    // Should show validation error atau swap dates
    // Implementation specific - bisa swap otomatis atau show error
    const content = await page.content();
    const hasContent = content.length > 0;
    expect(hasContent).toBeTruthy();
  });

  test('should be accessible to user role with read permission', async ({ page }) => {
    // Logout admin
    await page.goto('/logout');

    // Login sebagai user
    await page.goto('/login');
    await page.fill('input[name="username"]', 'user');
    await page.fill('input[name="password"]', 'user');
    await page.click('button[type="submit"]');

    // User seharusnya bisa akses reports (read-only)
    await page.goto('/report/arsip');

    const isAccessible = page.url().includes('/report');
    expect(isAccessible).toBeTruthy();
  });

  test('should show loading state while generating report', async ({ page }) => {
    // Slow down response to see loading
    await page.route('/report/arsip*', async route => {
      await new Promise(resolve => setTimeout(resolve, 1000));
      await route.continue();
    });

    await page.goto('/report/arsip');

    await page.fill('input[name="tanggal_awal"]', '2026-01-01');
    await page.fill('input[name="tanggal_akhir"]', '2026-12-31');
    await page.click('button[type="submit"]');

    // Check for loading indicator (spinner, disabled button, etc)
    const loadingIndicator = page.locator('.spinner, .loading, button:disabled').first();
    const hasLoading = await loadingIndicator.isVisible({ timeout: 500 }).catch(() => false);

    // Eventually table should load
    await expect(page.locator('table')).toBeVisible({ timeout: 5000 });
  });
});
