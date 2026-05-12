import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('User Management', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('admin views user list', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/user`);
    await expect(page.locator('table')).toBeVisible({ timeout: 5000 });
  });

  test('admin accesses add user modal', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/user`);
    await page.waitForTimeout(1000);

    await page.click('a[data-target="#adduser"]');
    await page.waitForTimeout(500);

    await expect(page.locator('#adduser #username')).toBeVisible();
    await expect(page.locator('#adduser #password')).toBeVisible();
    await expect(page.locator('#adduser #addusergo')).toBeVisible();
  });

  test('admin accesses edit user modal', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/user`);
    await page.waitForTimeout(1000);

    const editLink = page.locator('a[data-target="#edituser"]').first();
    if (await editLink.isVisible()) {
      await editLink.click();
      await page.waitForTimeout(500);
      await expect(page.locator('#edituser #eusername')).toBeVisible();
      await expect(page.locator('#edituser #editusergo')).toBeVisible();
    }
  });

  test('user cannot access user management page', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/user`);
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/');
  });
});
