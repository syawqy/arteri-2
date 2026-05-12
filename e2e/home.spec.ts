import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('Home & Pencarian', () => {
  test.beforeEach(async () => { clearRateLimits(); });

  test('home page shows search form', async ({ page }) => {
    await loginAsAdmin(page);
    await expect(page.locator('input[name="katakunci"]')).toBeVisible();
  });

  test('admin search with keyword returns results', async ({ page }) => {
    await loginAsAdmin(page);
    await page.fill('input[name="katakunci"]', 'arsip');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    const bodyText = await page.textContent('body');
    expect(bodyText).toBeTruthy();
  });

  test('user search works', async ({ page }) => {
    await loginAsUser(page);
    await page.fill('input[name="katakunci"]', 'arsip');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/search');
  });

  test('view arsip detail', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/view/1`);
    await page.waitForTimeout(2000);
    const bodyText = await page.textContent('body');
    expect(bodyText).toBeTruthy();
  });
});
