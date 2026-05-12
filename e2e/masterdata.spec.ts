import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('Master Data', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('admin views klasifikasi list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/master/klas`);
    await expect(page.locator('#vkode')).toBeVisible({ timeout: 5000 });
  });

  test('admin views pencipta list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/master/penc`);
    await expect(page.locator('#vpenc')).toBeVisible({ timeout: 5000 });
  });

  test('admin views pengolah list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/master/pengolah`);
    await expect(page.locator('#vpeng')).toBeVisible({ timeout: 5000 });
  });

  test('admin views lokasi list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/master/lokasi`);
    await expect(page.locator('#vlok')).toBeVisible({ timeout: 5000 });
  });

  test('admin views media list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/master/media`);
    await expect(page.locator('#vmed')).toBeVisible({ timeout: 5000 });
  });

  test('user cannot access klasifikasi page', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/master/klas`);
    await page.waitForTimeout(2000);
    expect(page.url()).not.toContain('/master/klas');
  });
});
