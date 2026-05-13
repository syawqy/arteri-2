<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class HomeControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private array $masterIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $db = \Config\Database::connect();

        $this->masterIds = [
            'kode'     => $db->table('master_kode')->get(1)->getRowArray()['id'],
            'pencipta' => $db->table('master_pencipta')->get(1)->getRowArray()['id'],
            'pengolah' => $db->table('master_pengolah')->get(1)->getRowArray()['id'],
            'lokasi'   => $db->table('master_lokasi')->get(1)->getRowArray()['id'],
            'media'    => $db->table('master_media')->get(1)->getRowArray()['id'],
        ];
    }

    private function getAdminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => ['entridata' => 'on'],
            'menu_master' => true,
        ];
    }

    private function getRegularUserSession(): array
    {
        return [
            'username'    => 'regular',
            'id_user'     => 3,
            'tipe'        => 'user',
            'akses_klas'  => '',
            'akses_modul' => ['entridata' => 'on'],
            'menu_master' => false,
        ];
    }

    private function insertArsipInDb(array $overrides = []): int
    {
        $db = \Config\Database::connect();
        $db->table('data_arsip')->insert(array_merge([
            'noarsip'       => 'HOME-001',
            'pencipta'      => $this->masterIds['pencipta'],
            'unit_pengolah' => $this->masterIds['pengolah'],
            'tanggal'       => '2025-06-01',
            'uraian'        => 'Home test arsip',
            'ket'           => 'asli',
            'kode'          => $this->masterIds['kode'],
            'jumlah'        => 1,
            'nobox'         => 'H-01',
            'lokasi'        => $this->masterIds['lokasi'],
            'media'         => $this->masterIds['media'],
            'username'      => 'admin',
        ], $overrides));

        return (int) $db->insertID();
    }

    public function testIndexRedirectsToSearch(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('/')->assertRedirectTo('search');
    }

    public function testGetSearchReturns200WhenLoggedIn(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        $this->get('search')->assertStatus(200);
    }

    public function testGetSearchWithAdvancedFilter(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        $this->get('search?ket=asli')->assertStatus(200);
    }

    public function testGetSearchWithOffset(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        $this->get('search/20')->assertStatus(200);
    }

    public function testGetViewRedirectsToLoginWhenNotLoggedIn(): void
    {
        $this->get('view/1')->assertRedirectTo('/login');
    }

    public function testDetailReturns200ForExisting(): void
    {
        $this->skipIfNotMysql();
        $id = $this->insertArsipInDb(['noarsip' => 'HOME-DETAIL-001']);

        $this->withSession($this->getAdminSession());
        $this->get('view/' . $id)->assertStatus(200);
    }

    public function testDetailThrows404ForNonexistentArsip(): void
    {
        $this->withSession($this->getAdminSession());

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->get('view/99999');
    }

    public function testSearchRequiresAuth(): void
    {
        $this->get('search')->assertRedirectTo('/login');
    }

    public function testViewRequiresAuth(): void
    {
        $this->get('view/1')->assertRedirectTo('/login');
    }

    public function testDownloadRequiresLogin(): void
    {
        $this->get('dl')->assertRedirectTo('/login');
    }

    public function testDownloadNotAccessibleWithoutAuth(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getRegularUserSession());
        ob_start();
        $response = $this->get('dl');
        ob_end_clean();
        $response->assertRedirectTo('/');
    }

    public function testDownloadReturnsOkWhenAuthenticated(): void
    {
        $this->skipIfNotMysql();

        $this->withSession($this->getAdminSession());
        ob_start();
        $response = $this->get('dl');
        ob_end_clean();
        $response->assertStatus(200);

        $contentType = $response->response()->getHeaderLine('Content-Type');
        $this->assertStringContainsString('spreadsheet', $contentType);
    }

    public function testSearchWithKeywords(): void
    {
        $this->skipIfNotMysql();
        $this->insertArsipInDb(['noarsip' => 'SEARCH-KW-001']);

        $this->withSession($this->getAdminSession());
        $body = (string) $this->get('search?katakunci=SEARCH-KW')->getBody();
        $this->assertStringContainsString('SEARCH-KW-001', $body);
    }

    private function skipIfNotMysql(): void
    {
        $db = \Config\Database::connect();
        if ($db->DBDriver === 'SQLite3') {
            $this->markTestSkipped(
                'Home::search() uses MySQL functions via ArsipModel. Run against a MySQL test DB.'
            );
        }
    }
}
