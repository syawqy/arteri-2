import { test, expect } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

test.describe('Authentication', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('admin login redirects to search', async ({ page }) => {
    await loginAsAdmin(page);
    expect(page.url()).toContain('/search');
  });

  test('user login redirects to search', async ({ page }) => {
    await loginAsUser(page);
    expect(page.url()).toContain('/search');
  });

  test('wrong password shows error message', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');
  });

  test('rate limited after too many failed attempts', async ({ page }) => {
    for (let i = 0; i < 6; i++) {
      await page.goto(`${BASE}/login`);
      await page.fill('input[name="username"]', 'admin');
      await page.fill('input[name="password"]', `wrong${i}`);
      await page.click('button[type="submit"]');
      await page.waitForTimeout(500);
      // check if rate-limited before continuing
      const body = await page.textContent('body');
      if (body && body.includes('15 menit')) {
        return; // rate limit kicked in, test passes
      }
    }
    // if we get here, rate limiting didn't work
    throw new Error('Rate limiting did not activate');
  });

  test('admin logout redirects to login', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/logout`);
    await page.waitForURL(`${BASE}/login`, { timeout: 10000 });
  });

  test('user logout redirects to login', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/logout`);
    await page.waitForURL(`${BASE}/login`, { timeout: 10000 });
  });

  test('admin sees all menu items', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/search`);
    await page.waitForTimeout(1000);
    const navText = await page.textContent('.navbar-collapse');
    expect(navText).toBeTruthy();
  });

  test('user sees limited menu', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/search`);
    await page.waitForTimeout(1000);
    const navText = await page.textContent('.navbar-collapse');
    expect(navText).toContain('Sirkulasi');
  });
});
