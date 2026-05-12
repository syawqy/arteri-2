import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('Arsip', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('admin accesses create arsip form', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/arsip/new`);
    await page.waitForTimeout(1000);
    await expect(page.locator('input[name="noarsip"]')).toBeVisible();
    await expect(page.locator('textarea[name="uraian"]')).toBeVisible();
    await expect(page.locator('#singlebutton')).toBeVisible();
  });

  test('admin accesses edit arsip page', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/search`);
    await page.waitForTimeout(1000);
    const editLink = page.locator('a[href*="/arsip/edit/"]').first();
    if (await editLink.isVisible()) {
      await editLink.click();
      await page.waitForTimeout(1000);
      expect(page.url()).toMatch(/\/arsip\/edit\/\d+/);
    }
  });

  test('user cannot access arsip create page', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/arsip/new`);
    await page.waitForTimeout(2000);
    const body = await page.textContent('body');
    expect(body).toContain('Pencarian');
  });
});
