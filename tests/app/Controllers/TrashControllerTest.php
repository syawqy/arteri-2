<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Feature test untuk Trash / Recycle Bin (admin only, task 7b).
 *
 * @internal
 */
final class TrashControllerTest extends CIUnitTestCase
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

    private function insertSoftDeletedArsip(array $overrides = []): int
    {
        $db = \Config\Database::connect();
        $db->table('data_arsip')->insert(array_merge([
            'noarsip'       => 'TRASH-001',
            'pencipta'      => $this->masterIds['pencipta'],
            'unit_pengolah' => $this->masterIds['pengolah'],
            'tanggal'       => '2025-06-01',
            'uraian'        => 'Trash test arsip',
            'ket'           => 'asli',
            'kode'          => $this->masterIds['kode'],
            'jumlah'        => 1,
            'nobox'         => 'T-01',
            'lokasi'        => $this->masterIds['lokasi'],
            'media'         => $this->masterIds['media'],
            'username'      => 'admin',
            'deleted_at'    => date('Y-m-d H:i:s'),
        ], $overrides));

        return (int) $db->insertID();
    }

    // ── Auth gate ──

    public function testIndexRequiresLogin(): void
    {
        $this->get('trash')->assertRedirectTo('/login');
    }

    public function testIndexForbiddenForNonAdmin(): void
    {
        $this->withSession($this->getRegularUserSession());
        $this->get('trash')->assertRedirectTo('/');
    }

    public function testIndexReturns200ForAdmin(): void
    {
        $this->withSession($this->getAdminSession());
        $this->get('trash')->assertStatus(200);
    }

    public function testRestoreForbiddenForNonAdmin(): void
    {
        $this->withSession($this->getRegularUserSession());
        $body = (string) $this->post('trash/restore', ['type' => 'arsip', 'id' => '1'])->getBody();
        $this->assertStringContainsString('error', $body);
    }

    // ── Restore ──

    public function testRestoreArsip(): void
    {
        $id = $this->insertSoftDeletedArsip(['noarsip' => 'TRASH-RESTORE']);

        $this->withSession($this->getAdminSession());
        $response = $this->post('trash/restore', ['type' => 'arsip', 'id' => (string) $id]);
        $this->assertStringContainsString('success', (string) $response->getBody());

        $db = \Config\Database::connect();
        $row = $db->table('data_arsip')->where('id', $id)->get()->getRowArray();
        $this->assertNull($row['deleted_at']);
    }

    public function testRestoreMasterBlockedByLiveDuplicate(): void
    {
        $db = \Config\Database::connect();

        // Soft-deleted master_kode + live duplicate dengan kode sama.
        $db->table('master_kode')->insert(['kode' => 'DUP.01', 'nama' => 'Trashed', 'retensi' => 1, 'deleted_at' => date('Y-m-d H:i:s')]);
        $trashedId = (int) $db->insertID();
        $db->table('master_kode')->insert(['kode' => 'DUP.01', 'nama' => 'Live', 'retensi' => 1]);

        $this->withSession($this->getAdminSession());
        $response = $this->post('trash/restore', ['type' => 'kode', 'id' => (string) $trashedId]);
        $body = (string) $response->getBody();
        $this->assertStringContainsString('error', $body);

        // Tetap soft-deleted.
        $row = $db->table('master_kode')->where('id', $trashedId)->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    // ── Purge ──

    public function testPurgeArsipRemovesRowAndFile(): void
    {
        $uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR;
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileName = 'trash-purge-test.pdf';
        file_put_contents($uploadDir . $fileName, 'dummy');

        $id = $this->insertSoftDeletedArsip(['noarsip' => 'TRASH-PURGE', 'file' => $fileName]);

        $this->withSession($this->getAdminSession());
        $response = $this->post('trash/purge', ['type' => 'arsip', 'id' => (string) $id]);
        $this->assertStringContainsString('success', (string) $response->getBody());

        $db = \Config\Database::connect();
        $row = $db->table('data_arsip')->where('id', $id)->get()->getRowArray();
        $this->assertNull($row); // hard-deleted
        $this->assertFileDoesNotExist($uploadDir . $fileName);
    }

    public function testPurgeInvalidTypeReturnsError(): void
    {
        $this->withSession($this->getAdminSession());
        $body = (string) $this->post('trash/purge', ['type' => 'bogus', 'id' => '1'])->getBody();
        $this->assertStringContainsString('error', $body);
    }
}
