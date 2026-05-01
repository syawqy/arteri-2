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

    public function testGetSearchReturns200WhenLoggedIn(): void
    {
        // Home::search() calls ArsipModel::search() which uses MySQL-specific
        // SQL (DATE_ADD, CURDATE, REGEXP). Skip on SQLite.
        $this->skipIfNotMysql();

        $this->withSession([
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => ['entridata' => 'on'],
            'menu_master' => true,
        ]);

        $this->get('search')->assertStatus(200);
    }

    public function testGetViewRedirectsToLoginWhenNotLoggedIn(): void
    {
        $this->get('view/1')->assertRedirectTo('/login');
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
