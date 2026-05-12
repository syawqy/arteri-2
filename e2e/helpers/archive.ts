import { Page, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import { mkdirSync, writeFileSync } from 'fs';
import path from 'path';
import { BASE } from './config';

export type ArchiveInput = {
  noarsip: string;
  tanggal?: string;
  uraian: string;
  ket?: 'asli' | 'copy';
  jumlah?: string;
  nobox?: string;
  kodeText?: string;
  filePath?: string;
};

export type ImportRow = {
  noarsip: string;
  tanggal?: string;
  uraian: string;
  kode?: string;
  ket?: 'asli' | 'copy';
  nobox?: string;
  jumlah?: number;
  pencipta?: string;
  pengolah?: string;
  lokasi?: string;
  media?: string;
  username?: string;
};

export function uniqueSuffix(prefix = 'e2e') {
  return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 8)}`;
}

export function fixturePath(name: string) {
  const dir = path.join('C:\\tmp', 'arteri-e2e-fixtures');
  mkdirSync(dir, { recursive: true });
  return path.join(dir, name);
}

export function writeFixture(name: string, contents: string | Buffer) {
  const target = fixturePath(name);
  writeFileSync(target, contents);
  return target;
}

export async function createArchive(page: Page, input: ArchiveInput): Promise<number> {
  await page.goto(`${BASE}/arsip/new`, { waitUntil: 'networkidle' });

  await page.fill('#noarsip', input.noarsip);
  await page.fill('#tanggal', input.tanggal ?? '2099-01-01');
  await selectFirstOption(page, '#pencipta');
  await selectFirstOption(page, '#unitpengolah');

  if (input.kodeText) {
    await setSelectValue(page, '#kode', await optionValueContaining(page, '#kode', input.kodeText));
  } else {
    await selectFirstOption(page, '#kode');
  }

  await page.fill('#uraian', input.uraian);
  await selectFirstOption(page, '#lokasi');
  await selectFirstOption(page, '#media');
  await setSelectValue(page, '#ket', input.ket ?? 'asli');
  await page.fill('#jumlah', input.jumlah ?? '1');
  await page.fill('#nobox', input.nobox ?? '');

  if (input.filePath) {
    await page.setInputFiles('#file', input.filePath);
  }

  await page.click('#singlebutton');
  await expect(page).toHaveURL(/\/view\/\d+/, { timeout: 10000 });
  await expect(page.locator('body')).toContainText(input.noarsip);

  const match = page.url().match(/\/view\/(\d+)/);
  expect(match?.[1]).toBeTruthy();
  return Number(match![1]);
}

export async function expectSearchContains(page: Page, params: Record<string, string>, text: string, shouldFind = true) {
  await page.goto(`${BASE}/search?${new URLSearchParams(params).toString()}`, { waitUntil: 'domcontentloaded' });
  const resultArea = page.locator('#hslsrc');
  await expect(resultArea).toBeVisible();

  if (shouldFind) {
    await expect(resultArea).toContainText(text);
  } else {
    await expect(resultArea).not.toContainText(text);
  }
}

export async function createClassification(page: Page, kode: string, nama: string, retensi = '10') {
  await page.goto(`${BASE}/master/klas`, { waitUntil: 'networkidle' });
  await page.click('a[data-target="#addkode"]');
  await expect(page.locator('#addkode')).toBeVisible();
  await page.fill('#addkode #adkode', kode);
  await page.fill('#addkode #nama', nama);
  await page.fill('#addkode #retensi', retensi);

  const responsePromise = page.waitForResponse((response) =>
    response.url().includes('/master/klas/create') && response.request().method() === 'POST',
  );
  await page.click('#addkodego');
  const response = await responsePromise;
  expect(response.ok()).toBeTruthy();
  await expect(page.locator('#addkode')).toBeHidden({ timeout: 10000 });
  await expect(page.locator('#vkode')).toContainText(kode, { timeout: 10000 });
}

export function createImportWorkbook(fileName: string, rows: ImportRow[], writer: 'Xlsx' | 'Xls' = 'Xlsx') {
  const target = fixturePath(fileName);
  const headers = [
    'No.Arsip',
    'Tanggal',
    'Uraian',
    'Kode Klasifikasi',
    'Ket',
    'No.Box',
    'Jumlah',
    'Pencipta',
    'Pengolah',
    'Lokasi',
    'Media',
    'username',
  ];
  const normalizedRows = rows.map((row) => ({
    'No.Arsip': row.noarsip,
    Tanggal: row.tanggal ?? '2099-01-01',
    Uraian: row.uraian,
    'Kode Klasifikasi': row.kode ?? 'E2E.IMPORT',
    Ket: row.ket ?? 'asli',
    'No.Box': row.nobox ?? '',
    Jumlah: row.jumlah ?? 1,
    Pencipta: row.pencipta ?? 'E2E Pencipta Import',
    Pengolah: row.pengolah ?? 'E2E Pengolah Import',
    Lokasi: row.lokasi ?? 'E2E Lokasi Import',
    Media: row.media ?? 'E2E Media Import',
    username: row.username ?? 'admin',
  }));

  const rowsBase64 = Buffer.from(JSON.stringify(normalizedRows), 'utf8').toString('base64');
  const headersBase64 = Buffer.from(JSON.stringify(headers), 'utf8').toString('base64');
  const targetPhp = phpString(target.replace(/\\/g, '/'));

  const php = `
require __DIR__ . '/vendor/autoload.php';
$headers = json_decode(base64_decode('${headersBase64}'), true);
$rows = json_decode(base64_decode('${rowsBase64}'), true);
$spreadsheet = new \\PhpOffice\\PhpSpreadsheet\\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Import Data');
foreach ($headers as $idx => $header) {
    $column = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::stringFromColumnIndex($idx + 1);
    $sheet->setCellValue($column . '2', $header);
}
$rowIndex = 3;
foreach ($rows as $row) {
    foreach ($headers as $idx => $header) {
        $column = \\PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate::stringFromColumnIndex($idx + 1);
        $sheet->setCellValue($column . $rowIndex, $row[$header] ?? '');
    }
    $rowIndex++;
}
$writer = new \\PhpOffice\\PhpSpreadsheet\\Writer\\${writer}($spreadsheet);
$writer->save(${targetPhp});
`;

  execFileSync('php', ['-r', php], { cwd: process.cwd(), stdio: 'pipe' });
  return target;
}

export async function setSelectValue(page: Page, selector: string, value: string) {
  await page.locator(selector).evaluate(
    (select, selectedValue) => {
      const field = select as HTMLSelectElement;
      field.value = selectedValue as string;
      field.dispatchEvent(new Event('change', { bubbles: true }));
    },
    value,
  );
}

export async function optionValueContaining(page: Page, selector: string, text: string): Promise<string> {
  const value = await page.locator(selector).evaluate((select, optionText) => {
    const options = Array.from((select as HTMLSelectElement).options);
    return options.find((option) => option.textContent?.includes(optionText as string))?.value ?? '';
  }, text);

  expect(value).not.toBe('');
  return value;
}

async function selectFirstOption(page: Page, selector: string) {
  const value = await page.locator(selector).evaluate((select) => {
    const option = Array.from((select as HTMLSelectElement).options).find((item) => item.value !== '');
    return option?.value ?? '';
  });

  expect(value).not.toBe('');
  await setSelectValue(page, selector, value);
}

function phpString(value: string) {
  return `'${value.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;
}
