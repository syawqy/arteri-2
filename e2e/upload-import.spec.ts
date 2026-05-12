import { test, expect } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth';
import { BASE } from './helpers/config';
import {
  createArchive,
  createImportWorkbook,
  expectSearchContains,
  uniqueSuffix,
  writeFixture,
} from './helpers/archive';

test.describe.serial('Upload and import edge cases', () => {
  test.setTimeout(180000);

  test('archive attachment accepts pdf, doc, and docx uploads', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('upload');
    const files = [
      writeFixture(`${suffix}.pdf`, Buffer.from('%PDF-1.4\n% arteri e2e\n')),
      writeFixture(`${suffix}.doc`, 'arteri e2e legacy doc fixture'),
      writeFixture(`${suffix}.docx`, 'arteri e2e docx fixture'),
    ];

    for (const filePath of files) {
      await test.step(filePath, async () => {
        const ext = filePath.split('.').pop();
        const noarsip = `UPLOAD-${ext}-${suffix}`;
        await createArchive(page, {
          noarsip,
          uraian: `Upload ${ext} ${suffix}`,
          filePath,
        });
        await expect(page.locator('a[href*="/file/"]')).toBeVisible();
      });
    }
  });

  test('archive attachment ignores unsupported extension without creating a file link', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('upload-invalid');
    const filePath = writeFixture(`${suffix}.txt`, 'unsupported file type');
    const noarsip = `UPLOAD-TXT-${suffix}`;

    await createArchive(page, {
      noarsip,
      uraian: `Upload unsupported ${suffix}`,
      filePath,
    });

    await expect(page.locator('body')).toContainText(noarsip);
    await expect(page.locator('a[href*="/file/"]')).toHaveCount(0);
  });

  test('import accepts valid xlsx and xls workbooks and persists rows', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('import');
    const formats = [
      { ext: 'xlsx', writer: 'Xlsx' as const },
      { ext: 'xls', writer: 'Xls' as const },
    ];

    for (const format of formats) {
      const noarsip = `IMPORT-${format.ext.toUpperCase()}-${suffix}`;
      const workbook = createImportWorkbook(`${suffix}.${format.ext}`, [
        {
          noarsip,
          uraian: `Import ${format.ext} ${suffix}`,
          kode: `IMP${suffix.slice(-5).toUpperCase()}`,
          nobox: `IB-${format.ext}-${suffix}`,
        },
      ], format.writer);

      await page.goto(`${BASE}/import`, { waitUntil: 'networkidle' });
      await page.setInputFiles('#up_file', workbook);
      await page.click('#import_data input[type="submit"]');
      await expect(page).toHaveURL(/\/import$/, { timeout: 10000 });
      await expect(page.locator('.alert')).toContainText('1 data berhasil diimport', { timeout: 10000 });
      await expectSearchContains(page, { noarsip }, noarsip, true);
    }
  });

  test('import rejects unsupported csv and damaged xlsx files with visible errors', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('import-bad');
    const csvPath = writeFixture(`${suffix}.csv`, 'No.Arsip,Tanggal,Uraian\nBAD,2099-01-01,Bad csv');
    const brokenXlsx = writeFixture(`${suffix}.xlsx`, 'this is not a spreadsheet');

    await page.goto(`${BASE}/import`, { waitUntil: 'networkidle' });
    await page.setInputFiles('#up_file', csvPath);
    await page.click('#import_data input[type="submit"]');
    await expect(page).toHaveURL(/\/import$/, { timeout: 10000 });
    await expect(page.locator('.alert-danger')).toContainText('Format file tidak didukung', { timeout: 10000 });

    await page.setInputFiles('#up_file', brokenXlsx);
    await page.click('#import_data input[type="submit"]');
    await expect(page).toHaveURL(/\/import$/, { timeout: 10000 });
    await expect(page.locator('.alert-danger')).toContainText('Gagal import', { timeout: 10000 });
  });
});
