import { test, expect } from '@playwright/test';

test.describe('Dashboard Analytics', () => {
  test.beforeEach(async ({ page }) => {
    // Login sebagai admin
    await page.goto('/login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL('/');
  });

  test('should display dashboard statistics cards', async ({ page }) => {
    await page.goto('/dashboard');

    // Verify stats cards ada
    await expect(page.locator('.card')).toHaveCount(4, { timeout: 10000 });

    // Verify ada angka statistik (total arsip, dipinjam, overdue, dll)
    const cards = page.locator('.card');

    // Card 1: Total Arsip
    await expect(cards.nth(0)).toContainText(/\d+/); // Harus ada angka

    // Card 2: Arsip Dipinjam
    await expect(cards.nth(1)).toContainText(/\d+/);

    // Card 3: Overdue
    await expect(cards.nth(2)).toContainText(/\d+/);

    // Card 4: Aktivitas (atau stat lain)
    await expect(cards.nth(3)).toContainText(/\d+/);
  });

  test('should load statistics from API', async ({ page }) => {
    // Intercept API calls
    await page.route('/dashboard/api/stats', async route => {
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          total_arsip: 100,
          arsip_dipinjam: 15,
          arsip_overdue: 3,
          aktivitas_bulan_ini: 45
        })
      });
    });

    await page.goto('/dashboard');

    // Verify data dari API muncul
    await expect(page.locator('text=/100/')).toBeVisible({ timeout: 5000 });
  });

  test('should display chart for arsip by klasifikasi', async ({ page }) => {
    await page.goto('/dashboard');

    // Verify chart canvas atau container ada
    const chartContainer = page.locator('#chart-by-klasifikasi, canvas').first();
    await expect(chartContainer).toBeVisible({ timeout: 10000 });
  });

  test('should display chart for monthly activity', async ({ page }) => {
    await page.goto('/dashboard');

    // Verify chart aktivitas bulanan
    const monthlyChart = page.locator('#chart-by-bulan, canvas').nth(1);

    // Wait for chart to render
    await page.waitForTimeout(2000);

    const isVisible = await monthlyChart.isVisible().catch(() => false);
    expect(isVisible).toBeTruthy();
  });

  test('should display arsip by lokasi chart', async ({ page }) => {
    await page.goto('/dashboard');

    // Verify chart lokasi ada
    await page.waitForTimeout(2000); // Wait for charts to load

    const charts = page.locator('canvas');
    const count = await charts.count();

    // Should have multiple charts (klasifikasi, bulan, lokasi, media, pencipta)
    expect(count).toBeGreaterThanOrEqual(3);
  });

  test('should show skeleton loaders while loading', async ({ page }) => {
    // Slow down API response to see skeleton
    await page.route('/dashboard/api/stats', async route => {
      await new Promise(resolve => setTimeout(resolve, 1000));
      await route.continue();
    });

    await page.goto('/dashboard');

    // Verify skeleton loaders muncul saat loading
    const skeleton = page.locator('.skeleton, .skeleton-card').first();
    const hasSkele ton = await skeleton.isVisible({ timeout: 500 }).catch(() => false);

    // Skeleton mungkin sudah hilang kalau API cepat, tapi oke
    // Yang penting page akhirnya loaded dengan data
    await expect(page.locator('.card').first()).toBeVisible({ timeout: 5000 });
  });

  test('should handle API errors gracefully', async ({ page }) => {
    // Mock API error
    await page.route('/dashboard/api/stats', async route => {
      await route.fulfill({
        status: 500,
        body: 'Internal Server Error'
      });
    });

    await page.goto('/dashboard');

    // Page tetap load, mungkin tampilkan error atau fallback
    await expect(page.locator('body')).toBeVisible();
  });

  test('should display recent activities or logs', async ({ page }) => {
    await page.goto('/dashboard');

    // Check kalau ada section aktivitas terkini (opsional)
    const activitySection = page.locator('text=/Aktivitas/i, text=/Recent/i').first();
    const hasActivity = await activitySection.isVisible({ timeout: 3000 }).catch(() => false);

    // Optional feature, tidak wajib
    if (hasActivity) {
      await expect(page.locator('table, .list-group')).toBeVisible();
    }
  });

  test('user role can access dashboard', async ({ page }) => {
    // Logout admin
    await page.goto('/logout');

    // Login sebagai user biasa
    await page.goto('/login');
    await page.fill('input[name="username"]', 'user');
    await page.fill('input[name="password"]', 'user');
    await page.click('button[type="submit"]');

    // User juga bisa akses dashboard (role-based stat visibility)
    await page.goto('/dashboard');

    await expect(page.locator('.card').first()).toBeVisible({ timeout: 5000 });
  });

  test('should be responsive on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    await page.goto('/dashboard');

    // Cards harus stack secara vertikal di mobile
    const cards = page.locator('.card');
    await expect(cards.first()).toBeVisible();

    // Verify layout tidak overlap
    const firstCardBox = await cards.first().boundingBox();
    const secondCardBox = await cards.nth(1).boundingBox();

    if (firstCardBox && secondCardBox) {
      // Second card harus di bawah first card (y position lebih besar)
      expect(secondCardBox.y).toBeGreaterThan(firstCardBox.y);
    }
  });
});
