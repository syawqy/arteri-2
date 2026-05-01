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

// Arsip (CRUD)
$routes->get('arsip/new', 'Arsip::new');
$routes->post('arsip', 'Arsip::create');
$routes->get('arsip/edit/(:num)', 'Arsip::edit/$1');
$routes->post('arsip/update/(:num)', 'Arsip::update/$1');
$routes->post('arsip/delete/(:num)', 'Arsip::delete/$1');
$routes->post('arsip/delfile/(:num)', 'Arsip::deleteFile/$1');

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

// Import/Export
$routes->get('import', 'Import::index');
$routes->post('import', 'Import::doImport');
$routes->get('export', 'Export::index');
