<?php

namespace Tests\App\Commands;

use App\Models\ArsipModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Test untuk command `trash:purge` (hapus permanen >recoveryDays).
 *
 * @internal
 */
final class PurgeTrashTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private function insertArsip(string $noarsip, ?string $deletedAt): int
    {
        $db = \Config\Database::connect();
        $m  = [
            'kode'     => $db->table('master_kode')->get(1)->getRowArray()['id'],
            'pencipta' => $db->table('master_pencipta')->get(1)->getRowArray()['id'],
            'pengolah' => $db->table('master_pengolah')->get(1)->getRowArray()['id'],
            'lokasi'   => $db->table('master_lokasi')->get(1)->getRowArray()['id'],
            'media'    => $db->table('master_media')->get(1)->getRowArray()['id'],
        ];
        $db->table('data_arsip')->insert([
            'noarsip'       => $noarsip,
            'pencipta'      => $m['pencipta'],
            'unit_pengolah' => $m['pengolah'],
            'tanggal'       => '2025-06-01',
            'uraian'        => 'Purge test',
            'ket'           => 'asli',
            'kode'          => $m['kode'],
            'jumlah'        => 1,
            'nobox'         => 'P-01',
            'lokasi'        => $m['lokasi'],
            'media'         => $m['media'],
            'username'      => 'admin',
            'deleted_at'    => $deletedAt,
        ]);

        return (int) $db->insertID();
    }

    public function testPurgeRemovesOnlyOldDeletedRows(): void
    {
        $oldId    = $this->insertArsip('PURGE-OLD', date('Y-m-d H:i:s', strtotime('-40 days')));
        $recentId = $this->insertArsip('PURGE-NEW', date('Y-m-d H:i:s', strtotime('-5 days')));

        command('trash:purge'); // default 30 hari

        $model = new ArsipModel();

        // Yang lama (40 hari) terhapus permanen.
        $this->assertNull($model->withDeleted()->find($oldId));

        // Yang baru (5 hari) masih ada di sampah.
        $recent = $model->onlyDeleted()->find($recentId);
        $this->assertNotNull($recent);
    }

    public function testPurgeWithDaysZeroRemovesAllTrashed(): void
    {
        $id = $this->insertArsip('PURGE-ALL', date('Y-m-d H:i:s', strtotime('-1 days')));

        command('trash:purge --days 0');

        $model = new ArsipModel();
        $this->assertNull($model->withDeleted()->find($id));
    }
}
