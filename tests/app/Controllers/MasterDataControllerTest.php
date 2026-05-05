<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 *
 * MasterData routes use CI3-style keys. Mapping:
 *   route key → table              → model
 *   klas      → master_kode        → MasterKodeModel
 *   penc      → master_pencipta    → MasterPenciptaModel
 *   pengolah  → master_pengolah    → MasterPengolahModel
 *   lokasi    → master_lokasi      → MasterLokasiModel
 *   media     → master_media       → MasterMediaModel
 *
 * CRUD AJAX routes: POST /master/{key}/{action}
 *   action: create, get, update, delete
 * List routes: GET /master/{key}
 * Reload routes: GET /master/{key}/reload
 */
final class MasterDataControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    /**
     * Route key → table name mapping.
     */
    private const TABLE_MAP = [
        'klas'     => 'master_kode',
        'penc'     => 'master_pencipta',
        'pengolah' => 'master_pengolah',
        'lokasi'   => 'master_lokasi',
        'media'    => 'master_media',
    ];

    private function getAdminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => [
                'entridata'   => 'on',
                'klasifikasi' => 'on',
                'pencipta'    => 'on',
                'pengolah'    => 'on',
                'lokasi'      => 'on',
                'media'       => 'on',
            ],
            'menu_master' => true,
        ];
    }

    // ====================================================================
    //  Auth gate
    // ====================================================================

    public function testListPageRequiresAuth(): void
    {
        $this->get('master/klas')->assertRedirectTo('/login');
    }

    public function testCreateRequiresAuth(): void
    {
        $this->post('master/klas/create', ['kode' => 'TST'])->assertRedirectTo('/login');
    }

    public function testUpdateRequiresAuth(): void
    {
        $this->post('master/klas/update', ['id' => '1'])->assertRedirectTo('/login');
    }

    public function testDeleteRequiresAuth(): void
    {
        $this->post('master/klas/delete', ['id' => '1'])->assertRedirectTo('/login');
    }

    // ====================================================================
    //  List pages — data provider per route key
    // ====================================================================

    /** @return list<array{string}> */
    public static function provideListRoutes(): array
    {
        return [
            ['klas'], ['penc'], ['pengolah'], ['lokasi'], ['media'],
        ];
    }

    /** @dataProvider provideListRoutes */
    public function testListReturns200(string $routeKey): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('master/' . $routeKey)->assertStatus(200);
    }

    /** @dataProvider provideListRoutes */
    public function testListWithKeyword(string $routeKey): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('master/' . $routeKey . '?katakunci=arsip')->assertStatus(200);
    }

    // ====================================================================
    //  Create
    // ====================================================================

    /** @return list<array{string, string, string, array}> */
    public static function provideCreateSuccess(): array
    {
        return [
            'klas'     => ['klas', 'post', 'master/klas/create', ['kode' => 'CRT.99', 'nama' => 'Baru', 'retensi' => '5']],
            'penc'     => ['penc', 'post', 'master/penc/create', ['nama' => 'Pencipta Baru']],
            'pengolah' => ['pengolah', 'post', 'master/pengolah/create', ['nama' => 'Pengolah Baru']],
            'lokasi'   => ['lokasi', 'post', 'master/lokasi/create', ['nama' => 'Lokasi Baru']],
            'media'    => ['media', 'post', 'master/media/create', ['nama' => 'Media Baru']],
        ];
    }

    /** @dataProvider provideCreateSuccess */
    public function testCreateSuccess(string $routeKey, string $method, string $route, array $payload): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->{$method}($route, $payload);
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    /** @return list<array{string, string, string}> */
    public static function provideCreateValidation(): array
    {
        return [
            ['klas', 'post', 'master/klas/create'],
            ['penc', 'post', 'master/penc/create'],
            ['pengolah', 'post', 'master/pengolah/create'],
            ['lokasi', 'post', 'master/lokasi/create'],
            ['media', 'post', 'master/media/create'],
        ];
    }

    /** @dataProvider provideCreateValidation */
    public function testCreateValidationError(string $routeKey, string $method, string $route): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->{$method}($route, []);
        $this->assertStringContainsString('error', (string) $response->getBody());
    }


    // ====================================================================
    //  Get (single record)
    // ====================================================================

    /** @return list<array{string, string, string}> */
    public static function provideGetRoutes(): array
    {
        return [
            'klas'     => ['klas', 'post', 'master/klas/get'],
            'penc'     => ['penc', 'post', 'master/penc/get'],
            'pengolah' => ['pengolah', 'post', 'master/pengolah/get'],
            'lokasi'   => ['lokasi', 'post', 'master/lokasi/get'],
            'media'    => ['media', 'post', 'master/media/get'],
        ];
    }

    /** @dataProvider provideGetRoutes */
    public function testGetReturnsRecord(string $routeKey, string $method, string $route): void
    {
        $this->withSession($this->getAdminSession());

        $db = \Config\Database::connect();
        $table = self::TABLE_MAP[$routeKey];
        $first = $db->table($table)->get(1)->getRowArray();
        $id = $first['id'];

        $response = $this->{$method}($route, ['id' => (string) $id]);
        $response->assertOK();
        $this->assertStringContainsString((string) $id, (string) $response->getBody());
    }

    /** @dataProvider provideGetRoutes */
    public function testGetReturnsEmptyForNonexistent(string $routeKey, string $method, string $route): void
    {
        $this->withSession($this->getAdminSession());
        $this->{$method}($route, ['id' => '99999'])->assertOK();
    }

    // ====================================================================
    //  Update
    // ====================================================================

    /** @return list<array{string, string, string, array}> */
    public static function provideUpdateRoutes(): array
    {
        return [
            'klas'     => ['klas', 'post', 'master/klas/update', ['kode' => 'UPD.99', 'nama' => 'Updated', 'retensi' => '3']],
            'penc'     => ['penc', 'post', 'master/penc/update', ['nama' => 'Updated']],
            'pengolah' => ['pengolah', 'post', 'master/pengolah/update', ['nama' => 'Updated']],
            'lokasi'   => ['lokasi', 'post', 'master/lokasi/update', ['nama' => 'Updated']],
            'media'    => ['media', 'post', 'master/media/update', ['nama' => 'Updated']],
        ];
    }

    /** @dataProvider provideUpdateRoutes */
    public function testUpdateSuccess(string $routeKey, string $method, string $route, array $payload): void
    {
        $this->withSession($this->getAdminSession());

        $db = \Config\Database::connect();
        $table = self::TABLE_MAP[$routeKey];
        $first = $db->table($table)->get(1)->getRowArray();

        $payload['id'] = (string) $first['id'];
        $response = $this->{$method}($route, $payload);
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    // ====================================================================
    //  Delete
    // ====================================================================

    /** @return list<array{string, string, string}> */
    public static function provideDeleteRoutes(): array
    {
        return [
            'klas'     => ['klas', 'post', 'master/klas/delete'],
            'penc'     => ['penc', 'post', 'master/penc/delete'],
            'pengolah' => ['pengolah', 'post', 'master/pengolah/delete'],
            'lokasi'   => ['lokasi', 'post', 'master/lokasi/delete'],
            'media'    => ['media', 'post', 'master/media/delete'],
        ];
    }

    /** @dataProvider provideDeleteRoutes */
    public function testDeleteDeletesNewRecord(string $routeKey, string $method, string $route): void
    {
        // Insert a fresh record guaranteed to have no arsip references
        $db = \Config\Database::connect();
        $table = self::TABLE_MAP[$routeKey];

        $insert = match ($routeKey) {
            'klas' => ['kode' => 'DEL.99', 'nama' => 'Deletable', 'retensi' => 1],
            'penc' => ['nama_pencipta' => 'Deletable'],
            'pengolah' => ['nama_pengolah' => 'Deletable'],
            'lokasi' => ['nama_lokasi' => 'Deletable'],
            'media' => ['nama_media' => 'Deletable'],
        };
        $db->table($table)->insert($insert);
        $id = $db->insertID();

        $this->withSession($this->getAdminSession());
        $response = $this->{$method}($route, ['id' => (string) $id]);
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    // ====================================================================
    //  Referenced delete protection
    // ====================================================================

    public function testDeleteKodeFailsWhenReferenced(): void
    {
        $db = \Config\Database::connect();
        $kodeRow  = $db->table('master_kode')->get(1)->getRowArray();
        $pencRow  = $db->table('master_pencipta')->get(1)->getRowArray();
        $pengRow  = $db->table('master_pengolah')->get(1)->getRowArray();
        $lokRow   = $db->table('master_lokasi')->get(1)->getRowArray();
        $medRow   = $db->table('master_media')->get(1)->getRowArray();

        $db->table('data_arsip')->insert([
            'noarsip'       => 'REF-001',
            'pencipta'      => $pencRow['id'],
            'unit_pengolah' => $pengRow['id'],
            'tanggal'       => '2025-01-01',
            'uraian'        => 'Ref arsip',
            'ket'           => 'asli',
            'kode'          => $kodeRow['id'],
            'jumlah'        => 1,
            'nobox'         => '',
            'lokasi'        => $lokRow['id'],
            'media'         => $medRow['id'],
            'username'      => 'admin',
        ]);

        $this->withSession($this->getAdminSession());
        $response = $this->post('master/klas/delete', ['id' => (string) $kodeRow['id']]);
        $this->assertStringContainsString('error', (string) $response->getBody());
        $this->assertStringContainsString('sedang digunakan', (string) $response->getBody());
    }

    // ====================================================================
    //  Reload (HTML table)
    // ====================================================================

    /** @return list<array{string}> */
    public static function provideReloadRoutes(): array
    {
        return [
            ['klas'], ['penc'], ['pengolah'], ['lokasi'], ['media'],
        ];
    }

    /** @dataProvider provideReloadRoutes */
    public function testReloadReturnsHtml(string $routeKey): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->get('master/' . $routeKey . '/reload');
        $response->assertStatus(200);
        $this->assertStringContainsString('table', (string) $response->getBody());
    }

    /** @dataProvider provideReloadRoutes */
    public function testReloadRequiresAuth(string $routeKey): void
    {
        $this->get('master/' . $routeKey . '/reload')->assertRedirectTo('/login');
    }
}
