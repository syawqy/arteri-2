<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\TestTraits\CsrfTestTrait;

/**
 * @internal
 */
final class SirkulasiControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use CsrfTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private array $masterIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupCsrf();

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
            'akses_modul' => ['sirkulasi' => 'on'],
            'menu_master' => false,
        ];
    }

    private function seedArsip(string $noarsip): int
    {
        $db = \Config\Database::connect();
        $db->table('data_arsip')->insert([
            'noarsip'       => $noarsip,
            'pencipta'      => $this->masterIds['pencipta'],
            'unit_pengolah' => $this->masterIds['pengolah'],
            'tanggal'       => '2025-01-01',
            'uraian'        => 'For sirkulasi',
            'ket'           => 'asli',
            'kode'          => $this->masterIds['kode'],
            'jumlah'        => 1,
            'nobox'         => '',
            'lokasi'        => $this->masterIds['lokasi'],
            'media'         => $this->masterIds['media'],
            'username'      => 'admin',
        ]);
        return (int) $db->insertID();
    }

    private function seedSirkulasi(string $noarsip): int
    {
        $db = \Config\Database::connect();
        $db->table('sirkulasi')->insert([
            'noarsip'           => $noarsip,
            'username_peminjam' => 'admin',
            'keperluan'         => 'Test pinjam',
            'tgl_pinjam'        => '2025-01-01 00:00:00',
            'tgl_haruskembali'  => '2025-01-15 00:00:00',
            'tgl_transaksi'     => '2025-01-01 08:00:00',
        ]);
        return (int) $db->insertID();
    }

    public function testGetSirkulasiRequiresAuth(): void
    {
        $this->get('sirkulasi')->assertRedirectTo('/login');
    }

    public function testNewFormRequiresAuth(): void
    {
        $this->get('sirkulasi/new')->assertRedirectTo('/login');
    }

    public function testCreateRequiresAuth(): void
    {
        $this->csrfPost('sirkulasi', [])->assertRedirectTo('/login');
    }

    public function testIndexReturns200(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('sirkulasi')->assertStatus(200);
    }

    public function testNewFormReturns200(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('sirkulasi/new')->assertStatus(200);
    }

    public function testCreateSuccess(): void
    {
        $this->seedArsip('SIR-CRT-001');
        $this->withSession($this->getAdminSession());

        $response = $this->csrfPost('sirkulasi', [
            'noarsip'           => 'SIR-CRT-001',
            'username_peminjam' => 'admin',
            'keperluan'         => 'Test peminjaman',
            'tgl_pinjam'        => '2025-01-01',
            'tgl_haruskembali'  => '2025-01-15',
        ]);
        $response->assertRedirectTo('/sirkulasi');
    }

    public function testCreateValidationError(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi', [
            'noarsip'  => '',
            'keperluan' => '',
        ]);
        $response->assertRedirect();
    }

    public function testEditReturns200ForExisting(): void
    {
        $this->seedArsip('SIR-EDIT-001');
        $id = $this->seedSirkulasi('SIR-EDIT-001');

        $this->withSession($this->getAdminSession());
        $this->get('sirkulasi/edit/' . $id)->assertStatus(200);
    }

    public function testEditRedirectsForNonexistent(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('sirkulasi/edit/99999')->assertRedirectTo('/sirkulasi');
    }

    public function testUpdateSuccess(): void
    {
        $this->seedArsip('SIR-UPD-001');
        $id = $this->seedSirkulasi('SIR-UPD-001');

        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi/update/' . $id, [
            'noarsip'           => 'SIR-UPD-001',
            'username_peminjam' => 'admin',
            'keperluan'         => 'Updated purpose',
            'tgl_pinjam'        => '2025-02-01',
            'tgl_haruskembali'  => '2025-02-15',
        ]);
        $response->assertRedirectTo('/sirkulasi');
    }

    public function testUpdateForNonexistentRedirects(): void
    {
        $this->withSession($this->getAdminSession());
        $this->csrfPost('sirkulasi/update/99999', [
            'noarsip'           => 'SIR-FAKE',
            'username_peminjam' => 'admin',
            'keperluan'         => 'Nope',
            'tgl_pinjam'        => '2025-01-01',
            'tgl_haruskembali'  => '2025-01-15',
        ])->assertRedirectTo('/sirkulasi');
    }

    public function testDeleteSuccess(): void
    {
        $this->seedArsip('SIR-DEL-001');
        $id = $this->seedSirkulasi('SIR-DEL-001');

        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi/delete/' . $id);
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    public function testKembaliSuccess(): void
    {
        $this->seedArsip('SIR-KMB-001');
        $id = $this->seedSirkulasi('SIR-KMB-001');

        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi/kembali/' . $id);
        $this->assertStringContainsString('success', (string) $response->getBody());
    }

    public function testKembaliFailsForNonexistent(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi/kembali/99999');
        $this->assertStringContainsString('error', (string) $response->getBody());
    }

    public function testXhrArsipReturnsEmptyForNoKeyword(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->get('ajax/arsip');
        $this->assertStringContainsString('[]', (string) $response->getBody());
    }

    public function testXhrArsipReturnsResults(): void
    {
        $this->seedArsip('AJX-001');
        $this->withSession($this->getAdminSession());

        $response = $this->get('ajax/arsip/AJX');
        $this->assertStringContainsString('AJX-001', (string) $response->getBody());
    }

    public function testXhrUserReturnsEmptyForNoKeyword(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->get('ajax/user');
        $this->assertStringContainsString('[]', (string) $response->getBody());
    }

    public function testXhrUserReturnsResults(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->get('ajax/user/admin');
        $this->assertStringContainsString('admin', (string) $response->getBody());
    }

    public function testCreateFailsWhenArsipNotFound(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi', [
            'noarsip'           => 'NONEXISTENT-ARSIP',
            'username_peminjam' => 'admin',
            'keperluan'         => 'Test',
            'tgl_pinjam'        => '2025-01-01',
            'tgl_haruskembali'  => '2025-01-15',
        ]);
        $response->assertRedirect();
    }

    public function testCreateFailsWhenUserNotFound(): void
    {
        $this->seedArsip('SIR-FK-USR');
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi', [
            'noarsip'           => 'SIR-FK-USR',
            'username_peminjam' => 'nonexistent_user',
            'keperluan'         => 'Test',
            'tgl_pinjam'        => '2025-01-01',
            'tgl_haruskembali'  => '2025-01-15',
        ]);
        $response->assertRedirect();
    }

    public function testDeleteWithInvalidIdReturnsError(): void
    {
        $this->withSession($this->getAdminSession());
        $response = $this->csrfPost('sirkulasi/delete', ['id' => 'invalid']);
        $this->assertStringContainsString('error', (string) $response->getBody());
    }
}

