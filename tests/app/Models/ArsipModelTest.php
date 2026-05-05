<?php

namespace Tests\App\Models;

use App\Models\ArsipModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ArsipModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private ArsipModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ArsipModel();
    }

    // ── MySQL-dependent tests (pertahankan) ──

    public function testSearchWithEmptyKeywordReturnsResults(): void
    {
        $this->skipIfNotMysql();

        $results = $this->model->search('', [], 20, 0);
        $this->assertIsArray($results);
    }

    public function testSearchCountReturnsInt(): void
    {
        $this->skipIfNotMysql();

        $count = $this->model->searchCount('');
        $this->assertIsInt($count);
    }

    public function testGetDetailReturnsNullForNonexistentId(): void
    {
        $this->skipIfNotMysql();

        $result = $this->model->getDetail(99999);
        $this->assertNull($result);
    }

    // ── Non-MySQL tests (basic CRUD, jalan di SQLite) ──

    public function testInsertAndFindArsip(): void
    {
        $data = $this->makeSampleArsipData();
        $id   = $this->model->insert($data, true);

        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('TST-ARSIP-001', $row['noarsip']);
        $this->assertSame('Test Uraian', $row['uraian']);
    }

    public function testUpdateArsip(): void
    {
        $id = $this->model->insert($this->makeSampleArsipData(), true);

        $this->model->update($id, ['uraian' => 'Updated Uraian']);
        $row = $this->model->find($id);
        $this->assertSame('Updated Uraian', $row['uraian']);
    }

    public function testDeleteArsip(): void
    {
        $id = $this->model->insert($this->makeSampleArsipData(), true);
        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testInsertSetsTimestamps(): void
    {
        $id = $this->model->insert($this->makeSampleArsipData(), true);

        $row = $this->model->find($id);
        $this->assertNotEmpty($row['tgl_input']);
    }

    public function testFindReturnsArrayWithAllowedFields(): void
    {
        $data = $this->makeSampleArsipData();
        $data['nonexistent'] = 'should-not-persist';
        $id = $this->model->insert($data, true);

        $row = $this->model->find($id);
        $this->assertArrayNotHasKey('nonexistent', $row);
    }

    // ── Helpers ──

    private function makeSampleArsipData(): array
    {
        // Resolve master IDs from seeder
        $db = \Config\Database::connect();

        $kode     = $db->table('master_kode')->where('kode', 'SDM.01')->get()->getRowArray();
        $pencipta = $db->table('master_pencipta')->get()->getFirstRow('array');
        $pengolah = $db->table('master_pengolah')->get()->getFirstRow('array');
        $lokasi   = $db->table('master_lokasi')->get()->getFirstRow('array');
        $media    = $db->table('master_media')->get()->getFirstRow('array');

        return [
            'noarsip'       => 'TST-ARSIP-001',
            'tanggal'       => '2025-06-01',
            'pencipta'      => $pencipta['id'],
            'unit_pengolah' => $pengolah['id'],
            'kode'          => $kode['id'],
            'uraian'        => 'Test Uraian',
            'lokasi'        => $lokasi['id'],
            'media'         => $media['id'],
            'ket'           => 'asli',
            'jumlah'        => 1,
            'nobox'         => 'B-01',
            'username'      => 'admin',
        ];
    }

    private function skipIfNotMysql(): void
    {
        $db = \Config\Database::connect();
        if ($db->DBDriver === 'SQLite3') {
            $this->markTestSkipped(
                'ArsipModel uses MySQL functions (DATE_ADD, CURDATE). Run against a MySQL test DB.'
            );
        }
    }
}
