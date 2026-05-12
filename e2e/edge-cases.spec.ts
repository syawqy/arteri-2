import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';
const FRAMEWORK_ERROR_PATTERNS = [
  'CodeIgniter\\Cache',
  'mysqli_sql_exception',
  'Undefined array key',
  'Undefined variable',
  'Call to a member function',
];

async function expectNoFrameworkError(page: Page) {
  const body = await page.textContent('body');
  expect(body).toBeTruthy();
  for (const pattern of FRAMEWORK_ERROR_PATTERNS) {
    expect(body ?? '').not.toContain(pattern);
  }
}

async function submitLogin(page: Page, username: string, password: string) {
  await page.goto(`${BASE}/login`);
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
}

test.describe('Edge cases', () => {
  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('login rejects empty credentials without server error', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.locator('input[required]').evaluateAll((fields) => {
      fields.forEach((field) => field.removeAttribute('required'));
    });
    await page.click('button[type="submit"]');

    await page.waitForURL('**/login', { timeout: 10000 });
    await expect(page.locator('body')).toContainText('Username dan password harus diisi.');
    await expectNoFrameworkError(page);
  });

  test('login treats SQL-looking credentials as invalid credentials only', async ({ page }) => {
    await submitLogin(page, "' OR '1'='1", "' OR '1'='1");

    await page.waitForURL('**/login', { timeout: 10000 });
    await expect(page.locator('body')).toContainText('Username atau password yang Anda masukkan salah.');
    await expectNoFrameworkError(page);
  });

  test('login does not redirect to an external previous URL', async ({ page }) => {
    await page.goto(`${BASE}/login`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin');
    await page.locator('input[name="previous"]').evaluate((field) => {
      (field as HTMLInputElement).value = 'https://evil.example/phishing';
    });
    await page.click('button[type="submit"]');

    await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 10000 });
    expect(page.url().startsWith(BASE)).toBeTruthy();
    expect(page.url()).toContain('/search');
    await expectNoFrameworkError(page);
  });

  test('search handles special characters without database or debug errors', async ({ page }) => {
    await loginAsAdmin(page);
    const payload = encodeURIComponent(`"><script>alert(1)</script>' OR 1=1 --`);
    await page.goto(`${BASE}/search?katakunci=${payload}&noarsip=${payload}&uraian=${payload}`);

    await expect(page.locator('input[name="katakunci"]')).toBeVisible({ timeout: 10000 });
    await expectNoFrameworkError(page);
  });

  test('missing archive detail returns 404 instead of a server crash', async ({ page }) => {
    await loginAsAdmin(page);
    const response = await page.goto(`${BASE}/view/999999999`);

    expect(response?.status()).toBe(404);
    await expectNoFrameworkError(page);
  });

  test('sirkulasi create rejects unknown archive and borrower safely', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/sirkulasi/new`);
    await page.fill('input[name="noarsip"]', 'EDGE-NOT-FOUND-999999');
    await page.fill('input[name="username_peminjam"]', 'missing-user-edge');
    await page.fill('textarea[name="keperluan"]', 'Regression edge test');
    await page.fill('input[name="tgl_pinjam"]', '2026-05-09');
    await page.fill('input[name="tgl_haruskembali"]', '2026-05-10');
    await page.click('button[type="submit"]');

    await expect(page.locator('#snoarsip')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('body')).toContainText('Arsip dengan nomor tersebut tidak ditemukan.');
    await expectNoFrameworkError(page);
  });
});
