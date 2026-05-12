import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('Sirkulasi', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('admin views sirkulasi list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/sirkulasi`);
    await expect(page.locator('table')).toBeVisible({ timeout: 5000 });
  });

  test('admin accesses new sirkulasi form', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/sirkulasi/new`);
    await page.waitForTimeout(1000);
    await expect(page.locator('#snoarsip')).toBeVisible();
    await expect(page.locator('#singlebutton')).toBeVisible();
  });

  test('user views sirkulasi list', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/sirkulasi`);
    await expect(page.locator('table')).toBeVisible({ timeout: 5000 });
  });

  test('user accesses new sirkulasi form', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/sirkulasi/new`);
    await page.waitForTimeout(1000);
    await expect(page.locator('#snoarsip')).toBeVisible();
  });
});
