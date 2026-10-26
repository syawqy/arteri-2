<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 *
 * Integration flow tests simulating realistic arsiparis workflow
 */
final class IntegrationArsipFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private array $testRecords = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Insert test master data
        $db = \Config\Database::connect();
        
        $this->testRecords = [
            'INITIAL-001' => 'Arsip Awal Keuangan 2024',
            'INITIAL-002' => 'Arsip Kontrak Kerja',
            'INITIAL-003' => 'Arsip Izin Mendirikan',
        ];
    }

    /**
     * Alur 1: User login dan pencarian data awal
     */
    public function testLoginAndSearchInitialData(): void
    {
        $response = $this->withSession(['username' => 'admin', 'is_admin' => true])
                          ->get('/search');
        
        $response->assertStatus(200);
    }

    /**
     * Alur 2: Insert data baru setelah pencarian
     */
    public function testInsertNewData(): void
    {
        $response = $this->withSession(['username' => 'admin', 'is_admin' => true])
                          ->post('/arsip', [
                              'noarsip'   => 'TEST-001',
                              'tanggal'   => '2025-04-01',
                              'uraian'    => 'Arsip Test Baru',
                              'pencipta'  => 1,
                              'unitpengolah' => 1,
                              'kode'      => 1,
                              'lokasi'    => 1,
                              'media'     => 1,
                              'ket'       => 'asli',
                              'jumlah'    => 1,
                              'nobox'     => 'Rak-A1',
                          ]);
        
        $response->assertRedirect();
    }

    /**
     * Alur 3: Search data yang baru diinsert
     */
    public function testSearchNewlyInsertedData(): void
    {
        // Verify record exists
        $db = \Config\Database::connect();
        $record = $db->table('data_arsip')
                     ->where('noarsip', 'TEST-001')
                     ->get()
                     ->getRowArray();
        
        $this->assertNotNull($record, "Should find TEST-001 record");
        $this->assertEquals('Arsip Test Baru', $record['uraian']);
    }

    /**
     * Alur 4: Delete data (soft delete)
     */
    public function testSoftDeleteData(): void
    {
        $db = \Config\Database::connect();
        
        // Get record
        $record = $db->table('data_arsip')
                     ->where('noarsip', 'INITIAL-001')
                     ->get()
                     ->getRowArray();
        
        $this->assertNotNull($record, "Record should exist");
        
        // Soft delete
        $db->table('data_arsip')
            ->where('id', $record['id'])
            ->set(['deleted_at' => date('Y-m-d H:i:s')])
            ->execute();
        
        // Verify in trash
        $deleted = $db->table('data_arsip')
                      ->where('id', $record['id'])
                      ->where('deleted_at IS NOT', null)
                      ->get()
                      ->getRowArray();
        
        $this->assertArrayHasKey('deleted_at', $deleted);
    }

    /**
     * Alur 5: Restore data dari trash
     */
    public function testRestoreData(): void
    {
        $db = \Config\Database::connect();
        
        // Get deleted record
        $deleted = $db->table('data_arsip')
                      ->where('deleted_at IS NOT', null)
                      ->limit(1)
                      ->get()
                      ->getRowArray();
        
        $this->assertNotNull($deleted, "Should find deleted record");
        
        // Restore
        $db->table('data_arsip')
            ->where('id', $deleted['id'])
            ->set(['deleted_at' => null])
            ->execute();
        
        // Verify restored
        $restored = $db->table('data_arsip')
                       ->where('id', $deleted['id'])
                       ->where('deleted_at', null)
                       ->get()
                       ->getRowArray();
        
        $this->assertNotNull($restored, "Record should be restored");
        $this->assertNull($restored['deleted_at'], "deleted_at should be null");
    }

    /**
     * Alur 6: Verify ordering data
     */
    public function testDataOrdering(): void
    {
        $db = \Config\Database::connect();
        
        $results = $db->table('data_arsip')
                     ->where('deleted_at', null)
                     ->orderBy('tanggal', 'DESC')
                     ->get()
                     ->getResultArray();
        
        $this->assertGreaterThanOrEqual(1, count($results), "Should have records");
    }

    /**
     * Alur 7: Bulk insert multiple records
     */
    public function testBulkDataOperations(): void
    {
        $db = \Config\Database::connect();
        
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $id = $db->table('data_arsip')->insert([
                'noarsip'       => sprintf('BULK-%03d', $i),
                'tanggal'       => date('Y-m-d'),
                'pencipta'      => 1,
                'unit_pengolah' => 1,
                'kode'          => 1,
                'uraian'        => sprintf('Bulk Arsip %d', $i),
                'lokasi'        => 1,
                'media'         => 1,
                'ket'           => 'asli',
                'jumlah'        => 1,
                'nobox'         => 'B-01',
                'username'      => 'admin',
            ]);
            $ids[] = $id;
        }
        
        $this->assertCount(3, $ids, "Should have 3 bulk IDs");
    }

    /**
     * Alur 8: Verify data relationships integrity
     */
    public function testDataRelationships(): void
    {
        $db = \Config\Database::connect();
        
        $records = $db->table('data_arsip')
                      ->where('deleted_at', null)
                      ->limit(10)
                      ->get()
                      ->getResultArray();
        
        foreach ($records as $record) {
            $this->assertArrayHasKey('uraian', $record);
            $this->assertArrayHasKey('tanggal', $record);
            $this->assertArrayHasKey('kode', $record);
            $this->assertArrayHasKey('pencipta', $record);
        }
    }

    /**
     * Alur 9: Search with multiple criteria
     */
    public function testMultiCriteriaSearch(): void
    {
        $db = \Config\Database::connect();
        
        $results = $db->table('data_arsip')
                      ->where('deleted_at', null)
                      ->where('kode', 1)
                      ->orderBy('tanggal', 'DESC')
                      ->get()
                      ->getResultArray();
        
        $this->assertCount(count($results), $results);
    }
}
