<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    public function testGetLoginReturns200(): void
    {
        $this->get('login')->assertStatus(200);
    }

    public function testPostLoginWithWrongCredentialsRedirectsToLogin(): void
    {
        $this->post('login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ])->assertRedirectTo('/login');
    }

    public function testGetLogoutRedirects(): void
    {
        $this->withSession([
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => json_encode(['entridata' => 'on']),
            'menu_master' => true,
        ]);

        $this->get('logout')->assertRedirect();
    }
}
