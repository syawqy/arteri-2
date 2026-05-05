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

    public function testGetViewRedirectsToLoginWhenNotLoggedIn(): void
    {
        $this->get('view/1')->assertRedirectTo('/login');
    }
    public function testDetailThrows404ForNonexistentArsip(): void
    {
        $this->withSession($this->getAdminSession());

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->get('view/99999');
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
