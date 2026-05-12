import { test, expect, Page } from '@playwright/test';
import { loginAs, loginAsAdmin } from './helpers/auth';
import { BASE } from './helpers/config';
import {
  createArchive,
  createClassification,
  expectSearchContains,
  setSelectValue,
  uniqueSuffix,
} from './helpers/archive';

test.describe.serial('Full CRUD for archive, master data, and user', () => {
  test.setTimeout(180000);

  test('admin creates, updates, and deletes an archive through the UI', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('crud-arsip');
    const noarsip = `CRUD-ARSIP-${suffix}`;
    const updatedUraian = `Uraian arsip terupdate ${suffix}`;
    const id = await createArchive(page, {
      noarsip,
      uraian: `Uraian arsip awal ${suffix}`,
      ket: 'asli',
      nobox: `BOX-${suffix}`,
    });

    await page.goto(`${BASE}/arsip/edit/${id}`, { waitUntil: 'networkidle' });
    await expect(page.locator('#noarsip')).toHaveValue(noarsip);
    await page.fill('#uraian', updatedUraian);
    await page.fill('#nobox', `BOX-UPDATED-${suffix}`);
    await setSelectValue(page, '#ket', 'copy');
    await page.click('#singlebutton');
    await expect(page).toHaveURL(new RegExp(`/view/${id}$`), { timeout: 10000 });
    await expect(page.locator('body')).toContainText(updatedUraian);
    await expect(page.locator('body')).toContainText('copy');

    await expectSearchContains(page, { noarsip }, updatedUraian, true);
    await page.locator('#hslsrc tr', { hasText: noarsip }).locator('.deldata').click();
    await expect(page.locator('#deldata')).toBeVisible();

    const responsePromise = page.waitForResponse((response) =>
      response.url().includes('/arsip/delete') && response.request().method() === 'POST',
    );
    await page.click('#deldatago');
    const response = await responsePromise;
    expect(response.ok()).toBeTruthy();
    await expect(page.locator('#deldata')).toBeHidden({ timeout: 10000 });
    await expectSearchContains(page, { noarsip }, noarsip, false);
  });

  test('admin creates, updates, and deletes classification master data through the UI', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('crud-klas');
    const kode = `K${suffix.slice(-7)}`.replace(/-/g, '').slice(0, 10).toUpperCase();
    const nama = `CRUD Klasifikasi ${suffix}`;
    const updatedNama = `CRUD Klasifikasi Updated ${suffix}`;

    await createClassification(page, kode, nama, '8');
    await page.locator('#vkode tr', { hasText: kode }).locator('.edkode').click();
    await expect(page.locator('#editkode')).toBeVisible();
    await expect(page.locator('#editkode #ekode')).toHaveValue(kode);
    await page.fill('#editkode #enama', updatedNama);
    await page.fill('#editkode #eretensi', '9');

    const updatePromise = page.waitForResponse((response) =>
      response.url().includes('/master/klas/update') && response.request().method() === 'POST',
    );
    await page.click('#editkodego');
    expect((await updatePromise).ok()).toBeTruthy();
    await expect(page.locator('#editkode')).toBeHidden({ timeout: 10000 });
    await expect(page.locator('#vkode')).toContainText(updatedNama);
    await expect(page.locator('#vkode')).toContainText('9 Tahun');

    await page.locator('#vkode tr', { hasText: updatedNama }).locator('.delkode').click();
    await expect(page.locator('#delkode')).toBeVisible();
    const deletePromise = page.waitForResponse((response) =>
      response.url().includes('/master/klas/delete') && response.request().method() === 'POST',
    );
    await page.click('#delkodego');
    expect((await deletePromise).ok()).toBeTruthy();
    await expect(page.locator('#delkode')).toBeHidden({ timeout: 10000 });
    await expect(page.locator('#vkode')).not.toContainText(updatedNama);
  });

  test('admin creates, validates persisted login, updates, and deletes a user through the UI', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('crud-user');
    const username = `usr_${suffix.replace(/-/g, '_')}`.slice(0, 40);
    const password = 'Pass1234';
    const updatedAccess = `sdm,${suffix.slice(-6)}`;

    await page.goto(`${BASE}/user`, { waitUntil: 'networkidle' });
    await page.click('a[data-target="#adduser"]');
    await expect(page.locator('#adduser')).toBeVisible();
    await page.fill('#adduser #username', username);
    await page.fill('#adduser #password', password);
    await page.fill('#adduser #conf_password', password);
    await page.fill('#adduser #akses_klas', 'sdm');
    await page.check('#adduser #modul1');
    await page.check('#adduser #modul9');
    await setSelectValue(page, '#adduser #tipe', 'user');

    const createPromise = page.waitForResponse((response) =>
      response.url().endsWith('/user') && response.request().method() === 'POST',
    );
    await page.click('#addusergo');
    expect((await createPromise).ok()).toBeTruthy();
    await expect(page.locator('#adduser')).toBeHidden({ timeout: 10000 });
    await expect(page.locator('#vuser')).toContainText(username);

    await loginAs(page, username, password);
    await expect(page.locator('.navbar-fixed-top')).toContainText(username);
    await loginAsAdmin(page);

    await page.goto(`${BASE}/user?katakunci=${encodeURIComponent(username)}`, { waitUntil: 'networkidle' });
    await page.locator('#vuser tr', { hasText: username }).locator('.eduser').click();
    await expect(page.locator('#edituser')).toBeVisible();
    await expect(page.locator('#edituser #eusername')).toHaveValue(username);
    await page.fill('#edituser #eakses_klas', updatedAccess);
    await page.check('#edituser #emodul3');

    const updatePromise = page.waitForResponse((response) =>
      response.url().includes('/user/update') && response.request().method() === 'POST',
    );
    await page.click('#editusergo');
    expect((await updatePromise).ok()).toBeTruthy();
    await expect(page.locator('#edituser')).toBeHidden({ timeout: 10000 });
    await expect(page.locator('#vuser')).toContainText(updatedAccess);

    await page.locator('#vuser tr', { hasText: username }).locator('.deluser').click();
    await expect(page.locator('#deluser')).toBeVisible();
    const deletePromise = page.waitForResponse((response) =>
      response.url().includes('/user/delete') && response.request().method() === 'POST',
    );
    await page.click('#delusergo');
    expect((await deletePromise).ok()).toBeTruthy();
    await expect(page.locator('#deluser')).toBeHidden({ timeout: 10000 });
    await expect(page.locator('#vuser')).not.toContainText(username);
  });

  test('admin creates, updates, and deletes secondary master data records through the UI', async ({ page }) => {
    await loginAsAdmin(page);

    const suffix = uniqueSuffix('crud-master');
    for (const config of secondaryMasterConfigs()) {
      await test.step(config.label, async () => {
        await crudSecondaryMaster(page, config, `${config.label} ${suffix}`);
      });
    }
  });
});

type SecondaryMasterConfig = {
  label: string;
  path: string;
  table: string;
  addTarget: string;
  addButton: string;
  editModal: string;
  editClass: string;
  editButton: string;
  deleteModal: string;
  deleteClass: string;
  deleteButton: string;
  createUrl: string;
  updateUrl: string;
  deleteUrl: string;
};

function secondaryMasterConfigs(): SecondaryMasterConfig[] {
  return [
    masterConfig('pencipta', 'master/penc', '#divtabelpenc', '#addpenc', '#addpencgo', '#editpenc', '.edpenc', '#editpencgo', '#delpenc', '.delpenc', '#delpencgo', '/master/penc/create', '/master/penc/update', '/master/penc/delete'),
    masterConfig('pengolah', 'master/pengolah', '#divtabelpeng', '#addpeng', '#addpenggo', '#editpeng', '.edpeng', '#editpenggo', '#delpeng', '.delpeng', '#delpenggo', '/master/pengolah/create', '/master/pengolah/update', '/master/pengolah/delete'),
    masterConfig('lokasi', 'master/lokasi', '#divtabellok', '#addlok', '#addlokgo', '#editlok', '.edlok', '#editlokgo', '#dellok', '.dellok', '#dellokgo', '/master/lokasi/create', '/master/lokasi/update', '/master/lokasi/delete'),
    masterConfig('media', 'master/media', '#divtabelmed', '#addmed', '#addmedgo', '#editmed', '.edmed', '#editmedgo', '#delmed', '.delmed', '#delmedgo', '/master/media/create', '/master/media/update', '/master/media/delete'),
  ];
}

function masterConfig(...args: [
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
  string,
]): SecondaryMasterConfig {
  const [
    label,
    path,
    table,
    addTarget,
    addButton,
    editModal,
    editClass,
    editButton,
    deleteModal,
    deleteClass,
    deleteButton,
    createUrl,
    updateUrl,
    deleteUrl,
  ] = args;

  return { label, path, table, addTarget, addButton, editModal, editClass, editButton, deleteModal, deleteClass, deleteButton, createUrl, updateUrl, deleteUrl };
}

async function crudSecondaryMaster(page: Page, config: SecondaryMasterConfig, name: string) {
  const updatedName = `${name} Updated`;

  await page.goto(`${BASE}/${config.path}`, { waitUntil: 'networkidle' });
  await page.click(`a[data-target="${config.addTarget}"]`);
  await expect(page.locator(config.addTarget)).toBeVisible();
  await page.fill(`${config.addTarget} input[name="nama"]`, name);

  const createPromise = page.waitForResponse((response) =>
    response.url().includes(config.createUrl) && response.request().method() === 'POST',
  );
  await page.click(config.addButton);
  expect((await createPromise).ok()).toBeTruthy();
  await expect(page.locator(config.addTarget)).toBeHidden({ timeout: 10000 });
  await expect(page.locator(config.table)).toContainText(name);

  await page.locator(`${config.table} tr`, { hasText: name }).locator(config.editClass).click();
  await expect(page.locator(config.editModal)).toBeVisible();
  await expect(page.locator(`${config.editModal} input[name="nama"]`)).toHaveValue(name);
  await page.fill(`${config.editModal} input[name="nama"]`, updatedName);

  const updatePromise = page.waitForResponse((response) =>
    response.url().includes(config.updateUrl) && response.request().method() === 'POST',
  );
  await page.click(config.editButton);
  expect((await updatePromise).ok()).toBeTruthy();
  await expect(page.locator(config.editModal)).toBeHidden({ timeout: 10000 });
  await expect(page.locator(config.table)).toContainText(updatedName);

  await page.locator(`${config.table} tr`, { hasText: updatedName }).locator(config.deleteClass).click();
  await expect(page.locator(config.deleteModal)).toBeVisible();
  const deletePromise = page.waitForResponse((response) =>
    response.url().includes(config.deleteUrl) && response.request().method() === 'POST',
  );
  await page.click(config.deleteButton);
  expect((await deletePromise).ok()).toBeTruthy();
  await expect(page.locator(config.deleteModal)).toBeHidden({ timeout: 10000 });
  await expect(page.locator(config.table)).not.toContainText(updatedName);
}
