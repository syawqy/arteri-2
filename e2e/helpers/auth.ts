import { Page, expect } from '@playwright/test';
import { clearRateLimits } from './seed';
import { BASE } from './config';

export async function loginAs(page: Page, username: string, password: string, shouldSucceed = true) {
  clearRateLimits();
  await page.context().clearCookies();
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' });
  await page.fill('input[name="username"]', username);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]');
  await page.waitForURL((url) => !url.pathname.includes('/login'), { timeout: 10000 });

  if (shouldSucceed) {
    await expect(page.locator('.navbar-fixed-top')).toBeVisible({ timeout: 5000 });
  }
}

export async function loginAsAdmin(page: Page) {
  await loginAs(page, 'admin', 'admin');
}

export async function loginAsUser(page: Page) {
  await loginAs(page, 'user', 'user');
}

export async function logout(page: Page) {
  await page.goto(`${BASE}/logout`);
  await page.waitForTimeout(1000);
}

export async function ensureLoggedOut(page: Page) {
  await page.goto(`${BASE}/logout`);
  await page.waitForTimeout(500);
}
