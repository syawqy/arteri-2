<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\TestTraits\CsrfTestTrait;

/**
 * @internal
 */
final class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CsrfTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCsrf();
    }

    private function getAdminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => [
                'entridata'   => 'on',
                'sirkulasi'   => 'on',
                'klasifikasi' => 'on',
                'pencipta'    => 'on',
                'pengolah'    => 'on',
                'lokasi'      => 'on',
                'media'       => 'on',
                'user'        => 'on',
                'import'      => 'on',
            ],
            'menu_master' => true,
        ];
    }

    public function testGetLoginReturns200(): void
    {
        $this->get('login')->assertStatus(200);
    }

    public function testGetLoginRedirectsToHomeWhenAlreadyLoggedIn(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('login')->assertRedirectTo('/');
    }

    public function testPostLoginWithWrongCredentialsRedirectsToLogin(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ])->assertRedirectTo('/login');
    }

    public function testPostLoginWithValidCredentialsRedirectsToHome(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'admin',
        ])->assertRedirectTo('/');
    }

    public function testPostLoginWithValidCredentialsSetsSession(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $this->assertSame('admin', session('username'));
        $this->assertSame('admin', session('tipe'));
    }

    public function testPostLoginRedirectsToPrevious(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'admin',
            'previous' => '/search',
        ])->assertRedirectTo('/search');
    }

    public function testGetLogoutRedirectsToLogin(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('logout')->assertRedirectTo('/login');
    }

    /**
     * Session destroy behavior differs in test environment.
     */
    public function testGetLogoutClearsSession(): void
    {
        $this->markTestSkipped('Session destroy in test env requires handler setup.');
    }

    public function testGetLogoutRedirectsWhenNotLoggedIn(): void
    {
        $this->get('logout')->assertRedirectTo('/login');
    }

    public function testRepeatedFailedLoginsAreRecorded(): void
    {
        $db = \Config\Database::connect();

        for ($i = 0; $i < 3; $i++) {
            $this->setupCsrf();
            $this->csrfPost('login', [
                'username' => 'bruteforce_user',
                'password' => 'wrong',
            ]);
        }

        $count = $db->table('login_attempts')
            ->where('username', 'bruteforce_user')
            ->countAllResults();

        $this->assertSame(3, $count);
    }

    public function testSuccessfulLoginCreatesAuditLog(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $db = \Config\Database::connect();
        $log = $db->table('system_log')
            ->where('aksi', 'LOGIN_SUCCESS')
            ->where('username_transaksi', 'admin')
            ->countAllResults();

        $this->assertGreaterThan(0, $log);
    }

    public function testFailedLoginCreatesAuditLog(): void
    {
        $this->csrfPost('login', [
            'username' => 'admin',
            'password' => 'wrong',
        ]);

        $db = \Config\Database::connect();
        $log = $db->table('system_log')
            ->where('aksi', 'LOGIN_FAILED')
            ->where('username_transaksi', 'admin')
            ->countAllResults();

        $this->assertGreaterThan(0, $log);
    }

    public function testPostLoginWithEmptyInputShowsError(): void
    {
        $this->csrfPost('login', [
            'username' => '',
            'password' => '',
        ])->assertRedirectTo('/login');
    }
}

