import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { BASE } from './helpers/config';

test.describe('Mobile responsive viewport', () => {
  test('admin can use main navigation and advanced search on mobile @responsive', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/search`, { waitUntil: 'networkidle' });

    await expect(page.locator('.navbar-fixed-top .navbar-toggle')).toBeVisible();
    await page.locator('.navbar-fixed-top .navbar-toggle').click();
    await expect(page.locator('#arteri-main-menu')).toBeVisible();
    await expect(page.locator('#arteri-main-menu')).toContainText('Data Master');

    await page.goto(`${BASE}/search`, { waitUntil: 'networkidle' });

    await expect(page.locator('.navbar-submenu .navbar-toggle')).toBeVisible();
    await page.locator('.navbar-submenu .navbar-toggle').click();
    await expect(page.locator('#module-submenu')).toBeVisible();
    await page.locator('.open-advanced-search').click();
    await expect(page.locator('#advanced-search')).toBeVisible();

    await page.fill('#uraian', 'mobile-responsive-smoke');
    await page.goto(`${BASE}/search?uraian=mobile-responsive-smoke`, { waitUntil: 'networkidle' });
    await expect(page).toHaveURL(/uraian=mobile-responsive-smoke/, { timeout: 10000 });
    await expect(page.locator('#tblhslsrc')).toBeVisible();
  });
});
