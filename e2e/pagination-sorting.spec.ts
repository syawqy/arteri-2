import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { BASE } from './helpers/config';
import { createClassification, createImportWorkbook, uniqueSuffix } from './helpers/archive';

test.describe.serial('Pagination, sorting, and advanced search volume', () => {
  test.setTimeout(180000);

  test('search paginates imported archive rows after 20 records', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('page');
    const keyword = `Pagination keyword ${suffix}`;
    const rows = Array.from({ length: 25 }, (_, index) => ({
      noarsip: `PAGE-${suffix}-${String(index + 1).padStart(2, '0')}`,
      uraian: `${keyword} row ${String(index + 1).padStart(2, '0')}`,
      kode: `PG${suffix.slice(-5).toUpperCase()}`,
      nobox: `PB-${suffix}`,
    }));
    const workbook = createImportWorkbook(`${suffix}.xlsx`, rows);

    await page.goto(`${BASE}/import`, { waitUntil: 'networkidle' });
    await page.setInputFiles('#up_file', workbook);
    await page.click('#import_data input[type="submit"]');
    await expect(page.locator('.alert')).toContainText('25 data berhasil diimport', { timeout: 10000 });

    await page.goto(`${BASE}/search?${new URLSearchParams({ katakunci: keyword }).toString()}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.well')).toContainText('(25)');
    await expect(page.locator('#hslsrc')).toContainText(rows[0].noarsip);
    await expect(page.locator('#hslsrc')).not.toContainText(rows[24].noarsip);
    await expect(page.locator('.pagination')).toContainText('2');

    await page.goto(`${BASE}/search/20?${new URLSearchParams({ katakunci: keyword }).toString()}`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#hslsrc')).toContainText(rows[24].noarsip);
  });

  test('classification master list is sorted ascending by code when filtered', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('sort').replace(/-/g, '').toUpperCase().slice(-5);
    const laterCode = `Z${suffix}`;
    const earlierCode = `A${suffix}`;
    const name = `Sort ${suffix}`;

    await createClassification(page, laterCode, `${name} Later`, '1');
    await createClassification(page, earlierCode, `${name} Earlier`, '1');

    await page.goto(`${BASE}/master/klas?katakunci=${encodeURIComponent(suffix)}`, { waitUntil: 'networkidle' });
    const codes = await page.locator('#vkode tbody tr td:first-child').allTextContents();
    const relevantCodes = codes.filter((code) => code.includes(suffix));
    expect(relevantCodes.slice(0, 2)).toEqual([earlierCode, laterCode]);
  });
});
