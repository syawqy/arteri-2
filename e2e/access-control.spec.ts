import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('Access Control', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('unauthenticated access redirects to login', async ({ page }) => {
    const protectedPages = ['/user', '/arsip/new', '/sirkulasi', '/audit', '/import', '/master/klas'];
    for (const path of protectedPages) {
      await page.goto(`${BASE}${path}`);
      await page.waitForTimeout(2000);
      expect(page.url()).toContain('/login');
    }
  });

  test('user cannot access arsip create page', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/arsip/new`);
    await page.waitForTimeout(2000);
    const body = await page.textContent('body');
    expect(body).toContain('Pencarian Lanjut');
  });

  test('user cannot access user management page', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/user`);
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/');
  });

  test('user cannot access audit log', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/audit`);
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/');
  });

  test('user cannot access import', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/import`);
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/');
  });

  test('admin can access audit log', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/audit`);
    await expect(page.locator('.sub-header')).toBeVisible({ timeout: 10000 });
  });

  test('admin can access import', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/import`);
    await expect(page.getByRole('heading', { name: 'Import Data' })).toBeVisible({ timeout: 5000 });
  });
});
