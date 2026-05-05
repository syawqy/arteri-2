<?php

namespace Tests\App\Models;

use App\Models\SirkulasiModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class SirkulasiModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private SirkulasiModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SirkulasiModel();
    }

    /**
     * Insert a minimal data_arsip record so the sirkulasi JOIN doesn't fail.
     */
    private function seedArsip(string $noarsip): void
    {
        $db = \Config\Database::connect();
        // Pick first IDs from seeded master tables
        $kode     = $db->table('master_kode')->get(1)->getRowArray();
        $pencipta = $db->table('master_pencipta')->get(1)->getRowArray();
        $pengolah = $db->table('master_pengolah')->get(1)->getRowArray();
        $lokasi   = $db->table('master_lokasi')->get(1)->getRowArray();
        $media    = $db->table('master_media')->get(1)->getRowArray();

        $db->table('data_arsip')->insert([
            'noarsip'       => $noarsip,
            'pencipta'      => $pencipta['id'],
            'unit_pengolah' => $pengolah['id'],
            'tanggal'       => '2025-01-01',
            'uraian'        => 'Test',
            'ket'           => 'asli',
            'kode'          => $kode['id'],
            'jumlah'        => 1,
            'nobox'         => 'B-01',
            'lokasi'        => $lokasi['id'],
            'media'         => $media['id'],
            'username'      => 'admin',
        ]);
    }

    private function makeSampleData(array $overrides = []): array
    {
        return array_merge([
            'noarsip'           => 'SIR-001',
            'username_peminjam' => 'admin',
            'keperluan'         => 'Test sirkulasi',
            'tgl_pinjam'        => '2025-01-01 00:00:00',
            'tgl_haruskembali'  => '2025-01-15 00:00:00',
            'tgl_transaksi'     => '2025-01-01 08:00:00',
        ], $overrides);
    }

    public function testInsertAndFind(): void
    {
        $this->seedArsip('SIR-001');
        $id = $this->model->insert($this->makeSampleData(), true);

        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('SIR-001', $row['noarsip']);
        $this->assertSame('admin', $row['username_peminjam']);
    }

    public function testSearchWithKeyword(): void
    {
        $this->seedArsip('XYZ-999');
        $this->model->insert($this->makeSampleData([
            'noarsip'           => 'XYZ-999',
            'username_peminjam' => 'admin',
            'keperluan'         => 'Searchable purpose',
        ]));

        $results = $this->model->search('admin', 10, 0);
        $this->assertCount(1, $results);
        $this->assertSame('XYZ-999', $results[0]['noarsip']);
    }

    public function testSearchWithKeywordNoMatchReturnsEmpty(): void
    {
        $this->seedArsip('NOMATCH-001');
        $this->model->insert($this->makeSampleData(['noarsip' => 'NOMATCH-001']));

        $results = $this->model->search('ZZZZNOTEXIST', 10, 0);
        $this->assertEmpty($results);
    }

    public function testSearchCount(): void
    {
        $this->seedArsip('CNT-001');
        $this->model->insert($this->makeSampleData(['noarsip' => 'CNT-001']));

        $countBefore = $this->model->searchCount('');

        $this->seedArsip('CNT-002');
        $this->model->insert($this->makeSampleData(['noarsip' => 'CNT-002']));

        $countAfter = $this->model->searchCount('');
        $this->assertSame($countBefore + 1, $countAfter);
    }

    public function testReturnArchive(): void
    {
        $this->seedArsip('RET-001');
        $id = $this->model->insert($this->makeSampleData([
            'noarsip' => 'RET-001',
        ]), true);

        $result = $this->model->returnArchive($id);
        $this->assertTrue($result);

        $row = $this->model->find($id);
        $this->assertNotNull($row['tgl_pengembalian']);
    }

    public function testSearchWithEmptyKeywordReturnsAll(): void
    {
        $this->seedArsip('ALL-001');
        $this->model->insert($this->makeSampleData(['noarsip' => 'ALL-001']));

        $this->seedArsip('ALL-002');
        $this->model->insert($this->makeSampleData(['noarsip' => 'ALL-002']));

        $results = $this->model->search('', 20, 0);
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
    }
}
