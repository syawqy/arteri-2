# CodeIgniter 4 — ARTERI Project Skills Reference

## Architecture & Patterns
- Extend all business controllers from `BaseController` (`app/Controllers/BaseController.php`) — Auth controller extends `Controller` directly since it doesn't need ACL
- Use **entity-configuration-map pattern** for grouped CRUD (see `MasterData::$entities` as reference — single controller drives multiple master data types via config array)
- Compose views manually with `view('layout/header', $data) . view('content') . view('layout/footer')` — no template engine or layout library
- Inject models inline with `new ModelName()` — no dependency injection container
- Place business logic methods directly in models (not in separate service/repository layers)
- Single migration file creates all tables; single seeder populates reference data
- No REST API endpoints — all responses are HTML views or JSON for AJAX

## Controllers
- PascalCase, singular noun naming: `Arsip`, `Sirkulasi`, `MasterData`, `User`, `Import`, `Export`
- Use `return view()` not `echo view()` — CI4 standard
- Load helpers via `protected $helpers = ['form', 'url', 'acl']` in `initController()`
- Validate with `$this->validate($rules)` returning arrays of rules
- Flash messages via `session()->setFlashdata('key', 'message')` with `session()->getFlashdata('key')` in views
- Redirect after POST with `return redirect()->to('route')`

## Models
- PascalCase, singular noun + `Model` suffix: `ArsipModel`, `UserModel`, `SirkulasiModel`
- Extend `CodeIgniter\Model`
- `$returnType = 'array'` (never objects/entities)
- `$useSoftDeletes = false`
- `$useTimestamps` only for main entity tables (`ArsipModel`), false for master reference tables
- Always set `$allowedFields` for mass-assignment protection
- Use Query Builder chaining for complex queries: `$this->builder()->select()->join()->where()->like()->groupStart()/groupEnd()`
- For raw/computed columns use builder expressions, not raw SQL strings
- Business methods (`attemptLogin()`, `returnArchive()`, `search()`) live in models

## Views
- Snake_case folders and files: `arsip/form.php`, `master/klasifikasi.php`, `sirkulasi/index.php`
- Use Bootstrap 3 with Glyphicons for UI
- Always escape output with `esc($var)` in views
- Pass title via `$data['title']`
- ACL-aware menu rendering: `<?php if (hasModuleAccess('arsip')): ?>` for conditional menu items
- User info via `session('username')` display
- AJAX + Modal pattern for all master data and user CRUD: form submit → JSON response → reload table via AJAX HTML replacement
- UI labels in Bahasa Indonesia
- Define helper functions in `app/Helpers/` files, never in view files

## Routes
- Explicit HTTP verb routing in `app/Config/Routes.php`: `$routes->get(...)`, `$routes->post(...)`
- Route groups for related endpoints: `$routes->group('master', function ($routes) { ... })`
- Segment placeholders: `(:num)`, `(:any)`, `(:segment)`
- No REST resource routes
- Lowercase route paths: `arsip/new`, `master/klas/create`, `user/delete/(:num)`

## Authentication & Authorization
- Custom session-based auth — check `session('username')` for login state
- `AuthFilter` (`app/Filters/AuthFilter.php`) as global `before` filter, except `login*` and `auth*`
- Password hashing: `password_hash($password, PASSWORD_BCRYPT)` / `password_verify()`
- ACL via `app/Helpers/acl_helper.php`: `isAdmin()`, `hasModuleAccess($module)`, `hasClassificationAccess($kode)`
- Module access stored as JSON in `master_user.akses_modul` (keys: 'on'/'off')
- Classification access stored as comma-separated prefixes in `master_user.akses_klas`
- Session stores: `username`, `id_user`, `tipe`, `akses_klas`, `akses_modul`, `menu_master`
- Login page is standalone (no layout header/footer)

## Database
- MySQL with MySQLi driver, `utf8mb4` charset
- Snake_case table names: `data_arsip`, `master_kode`, `master_user`, `sirkulasi`
- Snake_case column names: `nama_pencipta`, `tgl_haruskembali`, `akses_modul`
- Master tables use INT auto-increment IDs
- `data_arsip` references master tables via INT foreign-key-like columns (no actual FK constraints)
- Configure via `.env` file: `database.default.hostname`, `.database`, `.username`, `.password`
- `$strictOn = false` in Database config

## Configuration
- `.env`-based environment config: `CI_ENVIRONMENT`, database, app baseURL
- Session: `FileHandler`, cookie name `arteri`, 7200s expiration
- `$baseURL` in `App.php` without trailing slash: `http://localhost:8080/`
- `$indexPage = ''` (no index.php in URLs)
- Module auto-discovery enabled for events, filters, registrars, routes, services
- `$saveData = true` in View config

## Coding Conventions
- PHP 8.2+ features: `match` expressions, typed properties, union types
- Short array syntax `[]` everywhere
- `declare(strict_types=1)` at top of files
- Comments in English, UI labels in Bahasa Indonesia
- No — prefix for boolean or negation flags: `$useSoftDeletes`, `$strictOn`
- Use `esc()` for all output in views
- Validate all user input with CI4 validation rules arrays

## Common Tasks
- **Add new master data entity**: Add entry to `MasterData::$entities`, create view in `Views/master/`, add routes in group
- **Add new module**: Create controller extending `BaseController`, add model, create views folder, add routes, register in `akses_modul` JSON
- **Add AJAX endpoint**: Create controller method returning JSON via `$this->response->setJSON()`, add route
- **Add validation**: Define `$rules` array in controller method, call `$this->validate($rules)`, check with `$this->validator->getErrors()`
- **Add Excel import/export**: Use `PhpOffice\PhpSpreadsheet`, follow `Import` controller pattern
- **Run migration**: `php spark migrate`
- **Seed database**: `php spark db:seed`
