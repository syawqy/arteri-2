<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\TestTraits\CsrfTestTrait;

/**
 * @internal
 */
final class UserControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CsrfTestTrait;

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
            'akses_modul' => ['user' => 'on'],
            'menu_master' => true,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCsrf();
    }

    // ── Auth gate ──

    public function testIndexRequiresAuth(): void
    {
        $this->get('user')->assertRedirectTo('/login');
    }

    public function testCreateRequiresAuth(): void
    {
        $this->csrfPost('user', ['username' => 'test'])->assertRedirectTo('/login');
    }

    public function testDeleteRequiresAuth(): void
    {
        $this->csrfPost('user/delete', ['id' => '1'])->assertRedirectTo('/login');
    }

    // ── Index ──

    public function testIndexReturns200(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('user')->assertStatus(200);
    }

    // ── Create (with password policy) ──

    public function testCreateSuccess(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user', [
            'username'    => 'newuser',
            'password'    => 'secret123',
            'conf_password' => 'secret123',
            'tipe'        => 'user',
            'akses_klas'  => 'SDM,HKP',
            'modul'       => ['sirkulasi' => 'on'],
        ]);
        $response->assertOK();
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    public function testCreateFailsWhenPasswordTooShort(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user', [
            'username'     => 'newuser2',
            'password'     => 'abc1',
            'conf_password' => 'abc1',
            'tipe'         => 'user',
        ]);
        $response->assertOK();
        $body = (string) $response->getBody();
        $this->assertStringContainsString('error', $body);
    }

    public function testCreateFailsWhenPasswordMissingLetters(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user', [
            'username'     => 'newuser3',
            'password'     => '12345678',
            'conf_password' => '12345678',
            'tipe'         => 'user',
        ]);
        $response->assertOK();
        $body = (string) $response->getBody();
        $this->assertStringContainsString('error', $body);
    }

    public function testCreateFailsWhenPasswordMissingNumbers(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user', [
            'username'     => 'newuser4',
            'password'     => 'abcdefgh',
            'conf_password' => 'abcdefgh',
            'tipe'         => 'user',
        ]);
        $response->assertOK();
        $body = (string) $response->getBody();
        $this->assertStringContainsString('error', $body);
    }

    public function testCreateFailsWhenPasswordUnmatch(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user', [
            'username'     => 'newuser5',
            'password'     => 'secret123',
            'conf_password' => 'different1',
            'tipe'         => 'user',
        ]);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
    }

    public function testCreateFailsWhenUsernameExists(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user', [
            'username'     => 'admin',
            'password'     => 'secret123',
            'conf_password' => 'secret123',
            'tipe'         => 'user',
        ]);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
    }

    // ── Get ──

    public function testGetReturnsRecord(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user/get', ['id' => '1']);
        $response->assertOK();
        $this->assertStringContainsString('admin', (string) $response->getBody());
    }

    // ── Update ──

    public function testUpdateSuccess(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user/update', [
            'id'       => '1',
            'username' => 'admin',
            'tipe'     => 'admin',
        ]);
        $response->assertOK();
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    // ── Delete ──

    public function testDeleteSuccess(): void
    {
        $db = \Config\Database::connect();
        $db->table('master_user')->insert([
            'username'    => 'deletable',
            'password'    => password_hash('pass1234', PASSWORD_BCRYPT),
            'tipe'        => 'user',
            'akses_klas'  => '',
            'akses_modul' => '{}',
        ]);
        $id = $db->insertID();

        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user/delete', ['id' => (string) $id]);
        $response->assertOK();
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    public function testDeleteFailsForLastAdmin(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user/delete', ['id' => '1']);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
        $this->assertStringContainsString('admin terakhir', (string) $response->getBody());
    }

    // ── CekUsername ──

    public function testCekUsernameExists(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user/cekUsername', ['username' => 'admin']);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
    }

    public function testCekUsernameAvailable(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('user/cekUsername', ['username' => 'nonexistentuser']);
        $response->assertOK();
        $this->assertStringContainsString('ok', (string) $response->getBody());
    }

    // ── Access control for non-privileged users ──

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

    public function testNonAdminCannotCreateUser(): void
    {
        $this->withSession($this->getRegularUserSession());
        $response = $this->csrfPost('user', [
            'username'    => 'hacker',
            'password'    => 'secret123',
            'conf_password' => 'secret123',
            'tipe'        => 'user',
        ]);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
        $this->assertStringContainsString('Akses ditolak', (string) $response->getBody());
    }

    public function testNonAdminCannotGetUser(): void
    {
        $this->withSession($this->getRegularUserSession());
        $response = $this->csrfPost('user/get', ['id' => '1']);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
        $this->assertStringContainsString('Akses ditolak', (string) $response->getBody());
    }

    public function testNonAdminCannotDeleteUser(): void
    {
        $this->withSession($this->getRegularUserSession());
        $response = $this->csrfPost('user/delete', ['id' => '1']);
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
        $this->assertStringContainsString('Akses ditolak', (string) $response->getBody());
    }

    public function testNonAdminCannotViewUserPage(): void
    {
        $this->withSession($this->getRegularUserSession());
        $this->get('user')->assertRedirectTo('/');
    }

    public function testNonAdminCannotReloadUserTable(): void
    {
        $this->withSession($this->getRegularUserSession());
        $response = $this->get('user/reload');
        $response->assertOK();
        $this->assertStringContainsString('error', (string) $response->getBody());
        $this->assertStringContainsString('Akses ditolak', (string) $response->getBody());
    }
}


