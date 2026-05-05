<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuditLogControllerTest extends CIUnitTestCase
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
            'akses_modul' => ['user' => 'on'],
            'menu_master' => true,
        ];
    }

    private function getUserSession(): array
    {
        return [
            'username'    => 'user1',
            'id_user'     => 2,
            'tipe'        => 'user',
            'akses_klas'  => '',
            'akses_modul' => ['sirkulasi' => 'on'],
            'menu_master' => false,
        ];
    }

    public function testIndexRequiresAuth(): void
    {
        $this->get('audit')->assertRedirectTo('/login');
    }

    public function testIndexRejectsNonAdmin(): void
    {
        $this->withSession($this->getUserSession());
        $this->get('audit')->assertRedirectTo('/');
    }

    public function testIndexReturns200ForAdmin(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('audit')->assertStatus(200);
    }

    public function testIndexShowsNoDataMessageWhenEmpty(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->get('audit');
        $this->assertStringContainsString('Tidak ada data', (string) $response->getBody());
    }

    public function testIndexWithFilterByAksi(): void
    {
        $db = \Config\Database::connect();
        $db->table('system_log')->insert([
            'kode_transaksi'     => 'LOGIN_SUCCESS',
            'username_transaksi' => 'admin',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => 'LOGIN_SUCCESS',
        ]);

        $this->withSession($this->getAdminSession());
        $response = $this->get('audit?aksi=LOGIN_SUCCESS');
        $response->assertStatus(200);
        $this->assertStringContainsString('LOGIN_SUCCESS', (string) $response->getBody());
    }

    public function testDetailReturnsJsonForAdmin(): void
    {
        $db = \Config\Database::connect();
        $db->table('system_log')->insert([
            'kode_transaksi'     => 'CREATE',
            'username_transaksi' => 'admin',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => 'CREATE',
            'detail'             => json_encode(['field' => 'value']),
        ]);
        $id = $db->insertID();

        $this->withSession($this->getAdminSession());
        $response = $this->get('audit/detail/' . $id);
        $response->assertStatus(200);
        $this->assertStringContainsString('success', (string) $response->getBody());
        $this->assertStringContainsString('field', (string) $response->getBody());
    }

    public function testDetailReturnsErrorForNonAdmin(): void
    {
        $this->withSession($this->getUserSession());
        $response = $this->get('audit/detail/1');
        $response->assertStatus(200);
        $this->assertStringContainsString('ditolak', (string) $response->getBody());
    }
}
