import { test, expect, Page } from '@playwright/test';
import { BASE } from './helpers/config';
import { clearRateLimits } from './helpers/seed';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import {
  createArchive,
  createClassification,
  uniqueSuffix,
  writeFixture,
} from './helpers/archive';

const FRAMEWORK_ERROR_PATTERNS = [
  'mysqli_sql_exception',
  'CodeIgniter\\Database\\Exceptions',
  'CodeIgniter\\Debug\\Exceptions',
  'Undefined array key',
  'Call to a member function',
];

test.describe.serial('OWASP Top 10 security regression evidence', () => {
  test.setTimeout(180000);

  test.beforeEach(async ({ page }) => {
    clearRateLimits();
    await page.context().clearCookies();
  });

  test('A01/A07 protected routes require authentication and logout invalidates session', async ({ page }) => {
    for (const path of ['/user', '/arsip/new', '/master/klas', '/audit', '/import']) {
      await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
      await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
    }

    await loginAsAdmin(page);
    await page.goto(`${BASE}/logout`, { waitUntil: 'domcontentloaded' });
    await page.goto(`${BASE}/user`, { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
  });

  test('A01/A05 user role, CSRF, and security headers block unsafe access', async ({ page }) => {
    await loginAsUser(page);
    await page.goto(`${BASE}/user`, { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(new RegExp(`${escapeRegExp(BASE)}/?(search)?$`));

    await loginAsAdmin(page);
    const response = await page.goto(`${BASE}/search`, { waitUntil: 'domcontentloaded' });
    expect(response?.headers()['x-frame-options']).toBeTruthy();
    expect(response?.headers()['x-content-type-options']).toBe('nosniff');

    const csrfBypassStatus = await page.evaluate(async () => {
      const response = await fetch('/user', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'username=csrf_bypass&password=Pass1234&conf_password=Pass1234&tipe=user',
      });
      return response.status;
    });
    expect(csrfBypassStatus).toBe(403);
  });

  test('A01 direct archive detail and file IDOR respect user classification access', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('owasp-idor');
    const accessibleCode = `SDM${suffix.slice(-5)}`.replace(/-/g, '').toUpperCase();
    const restrictedCode = `ZZZ${suffix.slice(-5)}`.replace(/-/g, '').toUpperCase();
    const filePath = writeFixture(`${suffix}.pdf`, Buffer.from('%PDF-1.4\nOWASP IDOR fixture\n'));

    await createClassification(page, accessibleCode, `OWASP accessible ${suffix}`, '10');
    await createClassification(page, restrictedCode, `OWASP restricted ${suffix}`, '10');

    const accessibleId = await createArchive(page, {
      noarsip: `OWASP-ACCESS-${suffix}`,
      uraian: `Accessible OWASP ${suffix}`,
      kodeText: accessibleCode,
    });
    const restrictedId = await createArchive(page, {
      noarsip: `OWASP-RESTRICT-${suffix}`,
      uraian: `Restricted OWASP ${suffix}`,
      kodeText: restrictedCode,
      filePath,
    });
    const restrictedFileHref = await page.locator('a[href*="/file/"]').getAttribute('href');
    expect(restrictedFileHref).toBeTruthy();

    await loginAsUser(page);

    const allowed = await page.goto(`${BASE}/view/${accessibleId}`, { waitUntil: 'domcontentloaded' });
    expect(allowed?.status()).toBe(200);
    await expect(page.locator('body')).toContainText(`OWASP-ACCESS-${suffix}`);

    const denied = await page.goto(`${BASE}/view/${restrictedId}`, { waitUntil: 'domcontentloaded' });
    expect(denied?.status()).toBe(404);
    await expect(page.locator('body')).not.toContainText(`OWASP-RESTRICT-${suffix}`);

    const deniedFile = await page.goto(restrictedFileHref!, { waitUntil: 'domcontentloaded' });
    expect(deniedFile?.status()).toBe(404);
  });

  test('A03 login and search injection payloads fail safely without framework errors', async ({ page }) => {
    await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="username"]', `' OR '1'='1`);
    await page.fill('input[name="password"]', `' OR '1'='1`);
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
    await expect(page.locator('body')).toContainText('Username atau password yang Anda masukkan salah.');
    await expectNoFrameworkError(page);

    await loginAsAdmin(page);
    const payload = `"><script>window.__owaspInjection=true</script>' OR 1=1 --`;
    await page.goto(`${BASE}/search?katakunci=${encodeURIComponent(payload)}&uraian=${encodeURIComponent(payload)}`, {
      waitUntil: 'domcontentloaded',
    });
    await expect(page.locator('input[name="katakunci"]')).toBeVisible();
    await expectNoFrameworkError(page);
    expect(await page.evaluate(() => (window as any).__owaspInjection === true)).toBeFalsy();
  });

  test('A03/A08 stored XSS and path traversal payloads are rendered or rejected safely', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('owasp-xss');
    const payload = `<script id="owasp-xss">window.__owaspStoredXss=true</script>`;
    await createArchive(page, {
      noarsip: `OWASP-XSS-${suffix}`,
      uraian: payload,
    });

    await expect(page.locator('script#owasp-xss')).toHaveCount(0);
    expect(await page.evaluate(() => (window as any).__owaspStoredXss === true)).toBeFalsy();
    await expect(page.locator('body')).toContainText(payload);

    const traversal = await page.goto(`${BASE}/file/${encodeURIComponent('../.env')}`, {
      waitUntil: 'domcontentloaded',
    });
    expect(traversal?.status()).not.toBe(200);
  });
});

async function expectNoFrameworkError(page: Page) {
  const body = (await page.textContent('body')) ?? '';
  for (const pattern of FRAMEWORK_ERROR_PATTERNS) {
    expect(body).not.toContain(pattern);
  }
}

function escapeRegExp(value: string) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
