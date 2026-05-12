import { test, expect, Page } from '@playwright/test';
import { loginAsAdmin, loginAsUser } from './helpers/auth';
import { clearRateLimits } from './helpers/seed';
import { BASE } from './helpers/config';

type ArchiveFixture = {
  noarsip: string;
  tanggal: string;
  uraian: string;
  ket: 'asli' | 'copy';
  nobox: string;
  kode: string;
  kodeOptionValue: string;
  pencipta: string;
  penciptaOther: string;
  pengolah: string;
  pengolahOther: string;
  lokasi: string;
  lokasiOther: string;
  media: string;
  mediaOther: string;
};

const suffix = Date.now().toString(36);
const shortSuffix = suffix.slice(-4);
const accessibleCode = `SDM${shortSuffix}`;
const restrictedCode = `ZZZ${shortSuffix}`;
const accessibleName = `E2E SDM ${suffix}`;
const restrictedName = `E2E Restricted ${suffix}`;

let accessibleArchive: ArchiveFixture | null = null;
let restrictedArchive: ArchiveFixture | null = null;

test.describe.serial('Archive creation and search filters', () => {
  test.setTimeout(120000);

  test.beforeEach(async () => {
    clearRateLimits();
  });

  test('admin creates classification and archive records through the UI', async ({ page }) => {
    await ensureFixtures(page);

    await loginAsAdmin(page);
    await expectSearchResult(page, { noarsip: accessibleArchive!.noarsip }, accessibleArchive!.noarsip, true);
    await expectSearchResult(page, { noarsip: restrictedArchive!.noarsip }, restrictedArchive!.noarsip, true);
  });

  test('user classification filter only returns archives within user access', async ({ page }) => {
    await ensureFixtures(page);
    await loginAsUser(page);

    await expectSearchResult(
      page,
      { noarsip: accessibleArchive!.noarsip, kode: accessibleArchive!.kode },
      accessibleArchive!.noarsip,
      true,
    );

    await expectSearchResult(
      page,
      { noarsip: restrictedArchive!.noarsip, kode: restrictedArchive!.kode },
      restrictedArchive!.noarsip,
      false,
    );
  });

  test('admin advanced search applies every archive filter', async ({ page }) => {
    await ensureFixtures(page);
    await loginAsAdmin(page);

    const archive = accessibleArchive!;
    const filterCases = [
      {
        label: 'uraian',
        match: { noarsip: archive.noarsip, uraian: archive.uraian },
        miss: { noarsip: archive.noarsip, uraian: `missing ${suffix}` },
      },
      {
        label: 'tanggal',
        match: { noarsip: archive.noarsip, tanggal: archive.tanggal },
        miss: { noarsip: archive.noarsip, tanggal: '1999-01-01' },
      },
      {
        label: 'ket',
        match: { noarsip: archive.noarsip, ket: archive.ket },
        miss: { noarsip: archive.noarsip, ket: archive.ket === 'asli' ? 'copy' : 'asli' },
      },
      {
        label: 'kode klasifikasi',
        match: { noarsip: archive.noarsip, kode: archive.kode },
        miss: { noarsip: archive.noarsip, kode: restrictedArchive!.kode },
      },
      {
        label: 'retensi',
        match: { noarsip: archive.noarsip, retensi: 'belum' },
        miss: { noarsip: archive.noarsip, retensi: 'sudah' },
      },
      {
        label: 'pencipta',
        match: { noarsip: archive.noarsip, penc: archive.pencipta },
        miss: { noarsip: archive.noarsip, penc: archive.penciptaOther },
      },
      {
        label: 'unit pengolah',
        match: { noarsip: archive.noarsip, peng: archive.pengolah },
        miss: { noarsip: archive.noarsip, peng: archive.pengolahOther },
      },
      {
        label: 'lokasi',
        match: { noarsip: archive.noarsip, lok: archive.lokasi },
        miss: { noarsip: archive.noarsip, lok: archive.lokasiOther },
      },
      {
        label: 'media',
        match: { noarsip: archive.noarsip, med: archive.media },
        miss: { noarsip: archive.noarsip, med: archive.mediaOther },
      },
      {
        label: 'nobox',
        match: { noarsip: archive.noarsip, nobox: archive.nobox },
        miss: { noarsip: archive.noarsip, nobox: `missing-box-${suffix}` },
      },
    ];

    for (const filterCase of filterCases) {
      await test.step(`filters by ${filterCase.label}`, async () => {
        await expectSearchResult(page, filterCase.match, archive.noarsip, true);
        await expectSearchResult(page, filterCase.miss, archive.noarsip, false);
      });
    }
  });
});

async function ensureFixtures(page: Page) {
  if (accessibleArchive !== null && restrictedArchive !== null) {
    return;
  }

  await loginAsAdmin(page);
  await createClassification(page, accessibleCode, accessibleName, '10');
  await createClassification(page, restrictedCode, restrictedName, '10');

  accessibleArchive = await createArchive(page, {
    noarsip: `E2E-SDM-${suffix}`,
    tanggal: '2099-01-01',
    uraian: `Arsip akses user SDM ${suffix}`,
    ket: 'asli',
    nobox: `BS${shortSuffix}`,
    kode: accessibleCode,
  });

  restrictedArchive = await createArchive(page, {
    noarsip: `E2E-ZZZ-${suffix}`,
    tanggal: '2099-01-01',
    uraian: `Arsip restricted ${suffix}`,
    ket: 'asli',
    nobox: `BZ${shortSuffix}`,
    kode: restrictedCode,
  });
}

async function createClassification(page: Page, kode: string, nama: string, retensi: string) {
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

async function createArchive(
  page: Page,
  data: Pick<ArchiveFixture, 'noarsip' | 'tanggal' | 'uraian' | 'ket' | 'nobox' | 'kode'>,
): Promise<ArchiveFixture> {
  await page.goto(`${BASE}/arsip/new`, { waitUntil: 'networkidle' });

  const kodeOptionValue = await optionValueContaining(page, '#kode', data.kode);
  const [pencipta, penciptaOther] = await firstTwoOptionValues(page, '#pencipta');
  const [pengolah, pengolahOther] = await firstTwoOptionValues(page, '#unitpengolah');
  const [lokasi, lokasiOther] = await firstTwoOptionValues(page, '#lokasi');
  const [media, mediaOther] = await firstTwoOptionValues(page, '#media');

  await page.fill('#noarsip', data.noarsip);
  await page.fill('#tanggal', data.tanggal);
  await setSelectValue(page, '#pencipta', pencipta);
  await setSelectValue(page, '#unitpengolah', pengolah);
  await setSelectValue(page, '#kode', kodeOptionValue);
  await page.fill('#uraian', data.uraian);
  await setSelectValue(page, '#lokasi', lokasi);
  await setSelectValue(page, '#media', media);
  await setSelectValue(page, '#ket', data.ket);
  await page.fill('#jumlah', '1');
  await page.fill('#nobox', data.nobox);

  await page.click('#singlebutton');
  await expect(page).toHaveURL(/\/view\/\d+/, { timeout: 10000 });
  await expect(page.locator('body')).toContainText(data.noarsip);

  return {
    ...data,
    kodeOptionValue,
    pencipta,
    penciptaOther,
    pengolah,
    pengolahOther,
    lokasi,
    lokasiOther,
    media,
    mediaOther,
  };
}

async function expectSearchResult(
  page: Page,
  params: Record<string, string>,
  noarsip: string,
  shouldFind: boolean,
) {
  await page.goto(`${BASE}/search?${new URLSearchParams(params).toString()}`, { waitUntil: 'domcontentloaded' });
  const resultArea = page.locator('#hslsrc');
  await expect(resultArea).toBeVisible();

  if (shouldFind) {
    await expect(resultArea).toContainText(noarsip);
  } else {
    await expect(resultArea).not.toContainText(noarsip);
  }
}

async function optionValueContaining(page: Page, selector: string, text: string): Promise<string> {
  const value = await page.locator(selector).evaluate((select, optionText) => {
    const options = Array.from((select as HTMLSelectElement).options);
    return options.find((option) => option.textContent?.includes(optionText as string))?.value ?? '';
  }, text);

  expect(value).not.toBe('');
  return value;
}

async function firstTwoOptionValues(page: Page, selector: string): Promise<[string, string]> {
  const values = await page.locator(selector).evaluate((select) => {
    return Array.from((select as HTMLSelectElement).options)
      .map((option) => option.value)
      .filter((value) => value !== '');
  });

  expect(values.length).toBeGreaterThanOrEqual(2);
  return [values[0], values[1]];
}

async function setSelectValue(page: Page, selector: string, value: string) {
  await page.locator(selector).evaluate(
    (select, selectedValue) => {
      const field = select as HTMLSelectElement;
      field.value = selectedValue as string;
      field.dispatchEvent(new Event('change', { bubbles: true }));
    },
    value,
  );
}
