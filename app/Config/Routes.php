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
    $routes->resource('kode', ['controller' => 'MasterData::kode']);
    $routes->resource('pencipta', ['controller' => 'MasterData::pencipta']);
    $routes->resource('pengolah', ['controller' => 'MasterData::pengolah']);
    $routes->resource('lokasi', ['controller' => 'MasterData::lokasi']);
    $routes->resource('media', ['controller' => 'MasterData::media']);
});

// User
$routes->get('user', 'User::index');
$routes->post('user', 'User::create');
$routes->post('user/(:num)', 'User::update/$1');
$routes->delete('user/(:num)', 'User::delete/$1');

// Sirkulasi
$routes->get('sirkulasi', 'Sirkulasi::index');
$routes->get('sirkulasi/new', 'Sirkulasi::new');
$routes->post('sirkulasi', 'Sirkulasi::create');
$routes->get('sirkulasi/edit/(:num)', 'Sirkulasi::edit/$1');
$routes->post('sirkulasi/update/(:num)', 'Sirkulasi::update/$1');
$routes->post('sirkulasi/delete/(:num)', 'Sirkulasi::delete/$1');
$routes->post('sirkulasi/kembali/(:num)', 'Sirkulasi::kembali/$1');

// AJAX
$routes->get('ajax/arsip', 'Ajax::arsip');
$routes->get('ajax/user', 'Ajax::user');
$routes->get('ajax/master/(:segment)/reload', 'Ajax::masterReload/$1');

// Import/Export
$routes->get('import', 'Import::index');
$routes->post('import', 'Import::doImport');
$routes->get('export', 'Export::index');
