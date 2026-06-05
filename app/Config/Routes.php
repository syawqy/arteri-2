<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Auth
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::doLogin');
$routes->get('logout', 'Auth::logout');

// Home / Dashboard
$routes->get('search', 'Home::search');
$routes->get('search/(:num)', 'Home::search/$1');
$routes->get('dl', 'Home::download');
$routes->get('view/(:num)', 'Home::detail/$1');

// Dashboard
$routes->get('dashboard', 'Dashboard::index');
$routes->get('dashboard/api/stats', 'Dashboard::apiStats');
$routes->get('dashboard/api/summary', 'Dashboard::apiSummary');
$routes->get('dashboard/api/by-klasifikasi', 'Dashboard::apiByKlasifikasi');
$routes->get('dashboard/api/by-bulan', 'Dashboard::apiByBulan');
$routes->get('dashboard/api/by-lokasi', 'Dashboard::apiByLokasi');
$routes->get('dashboard/api/by-media', 'Dashboard::apiByMedia');
$routes->get('dashboard/api/by-pencipta', 'Dashboard::apiByPencipta');

// Report
$routes->get('report', 'Report::index');
$routes->get('report/arsip', 'Report::arsip');
$routes->get('report/arsip/export-excel', 'Report::exportArsipExcel');
$routes->get('report/arsip/print', 'Report::printArsip');
$routes->get('report/sirkulasi', 'Report::sirkulasi');
$routes->get('report/sirkulasi/export-excel', 'Report::exportSirkulasiExcel');
$routes->get('report/sirkulasi/print', 'Report::printSirkulasi');

// Arsip (CRUD)
$routes->get('arsip/new', 'Arsip::new');
$routes->post('arsip', 'Arsip::create');
$routes->get('arsip/edit/(:num)', 'Arsip::edit/$1');
$routes->post('arsip/update/(:num)', 'Arsip::update/$1');
$routes->post('arsip/delete/(:any)', 'Arsip::delete/$1');
$routes->post('arsip/delete', 'Arsip::delete');
$routes->post('arsip/delfile/(:any)', 'Arsip::deleteFile/$1');

// Master Data
$routes->group('master', function ($routes) {
    // List pages
    $routes->get('klas', 'MasterData::klas');
    $routes->get('penc', 'MasterData::penc');
    $routes->get('pengolah', 'MasterData::pengolah');
    $routes->get('lokasi', 'MasterData::lokasi');
    $routes->get('media', 'MasterData::media');

    // AJAX: Create
    $routes->post('klas/create', 'MasterData::createKode');
    $routes->post('penc/create', 'MasterData::createPenc');
    $routes->post('pengolah/create', 'MasterData::createPengolah');
    $routes->post('lokasi/create', 'MasterData::createLokasi');
    $routes->post('media/create', 'MasterData::createMedia');

    // AJAX: Update
    $routes->post('klas/update', 'MasterData::updateKode');
    $routes->post('penc/update', 'MasterData::updatePenc');
    $routes->post('pengolah/update', 'MasterData::updatePengolah');
    $routes->post('lokasi/update', 'MasterData::updateLokasi');
    $routes->post('media/update', 'MasterData::updateMedia');

    // AJAX: Delete
    $routes->post('klas/delete', 'MasterData::deleteKode');
    $routes->post('penc/delete', 'MasterData::deletePenc');
    $routes->post('pengolah/delete', 'MasterData::deletePengolah');
    $routes->post('lokasi/delete', 'MasterData::deleteLokasi');
    $routes->post('media/delete', 'MasterData::deleteMedia');

    // AJAX: Get single record
    $routes->post('klas/get', 'MasterData::getKode');
    $routes->post('penc/get', 'MasterData::getPenc');
    $routes->post('pengolah/get', 'MasterData::getPengolah');
    $routes->post('lokasi/get', 'MasterData::getLokasi');
    $routes->post('media/get', 'MasterData::getMedia');

    // AJAX: Reload table HTML
    $routes->get('klas/reload', 'MasterData::reloadKode');
    $routes->get('penc/reload', 'MasterData::reloadPenc');
    $routes->get('pengolah/reload', 'MasterData::reloadPengolah');
    $routes->get('lokasi/reload', 'MasterData::reloadLokasi');
    $routes->get('media/reload', 'MasterData::reloadMedia');
});

// User
$routes->get('user', 'User::index');
$routes->post('user', 'User::create');
$routes->post('user/update', 'User::update');
$routes->post('user/delete', 'User::delete');
$routes->post('user/get', 'User::get');
$routes->post('user/cekUsername', 'User::cekUsername');
$routes->get('user/reload', 'User::reload');

// Sirkulasi
$routes->get('sirkulasi', 'Sirkulasi::index');
$routes->get('sirkulasi/new', 'Sirkulasi::new');
$routes->post('sirkulasi', 'Sirkulasi::create');
$routes->get('sirkulasi/edit/(:num)', 'Sirkulasi::edit/$1');
$routes->post('sirkulasi/update/(:num)', 'Sirkulasi::update/$1');
$routes->post('sirkulasi/delete/(:num)', 'Sirkulasi::delete/$1');
$routes->post('sirkulasi/delete', 'Sirkulasi::delete');
$routes->post('sirkulasi/kembali/(:num)', 'Sirkulasi::kembali/$1');
$routes->post('sirkulasi/kembali', 'Sirkulasi::kembali');

// AJAX
$routes->get('ajax/arsip/(:any)', 'Sirkulasi::xhrArsip/$1');
$routes->get('ajax/arsip', 'Sirkulasi::xhrArsip');
$routes->get('ajax/user/(:any)', 'Sirkulasi::xhrUser/$1');
$routes->get('ajax/user', 'Sirkulasi::xhrUser');
$routes->get('ajax/master/(:segment)/reload', 'Ajax::masterReload/$1');

// File access (served through controller with ACL check)
$routes->get('file/(:segment)', 'FileController::serve/$1');

// Audit Log (admin only)
$routes->get('audit', 'AuditLog::index');
$routes->get('audit/detail/(:num)', 'AuditLog::detail/$1');

// Import/Export
$routes->get('import', 'Import::index');
$routes->post('import', 'Import::doImport');
$routes->get('export', 'Export::index');

// REST API (v1)
$routes->group('api/v1', function ($routes) {
    // Auth
    $routes->post('auth/login', 'Api\AuthController::login');
    $routes->post('auth/logout', 'Api\AuthController::logout');
    $routes->get('auth/me', 'Api\AuthController::me');

    // Arsip
    $routes->get('arsip', 'Api\ArsipController::index');
    $routes->get('arsip/(:num)', 'Api\ArsipController::show/$1');
    $routes->post('arsip', 'Api\ArsipController::create');
    $routes->put('arsip/(:num)', 'Api\ArsipController::update/$1');
    $routes->delete('arsip/(:num)', 'Api\ArsipController::delete/$1');

    // Sirkulasi
    $routes->get('sirkulasi', 'Api\SirkulasiController::index');
    $routes->get('sirkulasi/(:num)', 'Api\SirkulasiController::show/$1');
    $routes->post('sirkulasi', 'Api\SirkulasiController::create');
    $routes->delete('sirkulasi/(:num)', 'Api\SirkulasiController::delete/$1');
    $routes->post('sirkulasi/(:num)/kembali', 'Api\SirkulasiController::kembali/$1');

    // Master Data (generic by type: kode, pencipta, pengolah, lokasi, media)
    $routes->get('master/(:segment)', 'Api\MasterDataController::index/$1');
    $routes->get('master/(:segment)/(:num)', 'Api\MasterDataController::show/$1/$2');
    $routes->post('master/(:segment)', 'Api\MasterDataController::create/$1');
    $routes->put('master/(:segment)/(:num)', 'Api\MasterDataController::update/$1/$2');
    $routes->delete('master/(:segment)/(:num)', 'Api\MasterDataController::delete/$1/$2');

    // API key management (admin session)
    $routes->get('admin/api-keys', 'Api\ApiKeyController::index');
    $routes->post('admin/api-keys', 'Api\ApiKeyController::create');
    $routes->delete('admin/api-keys/(:num)', 'Api\ApiKeyController::revoke/$1');

    // OpenAPI spec & Swagger UI
    $routes->get('openapi.json', 'Api\DocsController::openapi');
    $routes->get('docs', 'Api\DocsController::ui');
});
