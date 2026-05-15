<?php

namespace Tests\App\Models;

use App\Models\SirkulasiModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\DatabaseTestTrait;

/**
 * Unit tests for SirkulasiModel
 * 
 * @group Model
 */
class SirkulasiModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\ArteriSeeder';

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SirkulasiModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test that model returns array type
     */
    public function testReturnType(): void
    {
        $result = $this->model->findAll(1);
        $this->assertIsArray($result);
    }

    /**
     * Test search method returns results
     */
    public function testSearchReturnsArray(): void
    {
        $results = $this->model->search('', 10);
        $this->assertIsArray($results);
    }

    /**
     * Test search with keywords returns filtered results
     */
    public function testSearchWithKeywords(): void
    {
        $results = $this->model->search('pinjam', 10);
        $this->assertIsArray($results);
    }

    /**
     * Test searchCount returns integer
     */
    public function testSearchCountReturnsInt(): void
    {
        $count = $this->model->searchCount();
        $this->assertIsInt($count);
    }

    /**
     * Test that allowed fields are correctly set
     */
    public function testAllowedFields(): void
    {
        $allowedFields = $this->model->allowedFields;
        
        $this->assertContains('noarsip', $allowedFields);
        $this->assertContains('username_peminjam', $allowedFields);
        $this->assertContains('keperluan', $allowedFields);
        $this->assertContains('tgl_pinjam', $allowedFields);
        $this->assertContains('tgl_haruskembali', $allowedFields);
    }

    /**
     * Test table name is correctly set
     */
    public function testTableName(): void
    {
        $this->assertEquals('sirkulasi', $this->model->table);
    }

    /**
     * Test that primary key is id
     */
    public function testPrimaryKey(): void
    {
        $this->assertEquals('id', $this->model->primaryKey);
    }

    /**
     * Test returnArchive method updates return date
     */
    public function testReturnArchive(): void
    {
        // First insert a test record
        $data = [
            'noarsip'           => 'RETURN-TEST-' . time(),
            'username_peminjam' => 'admin',
            'keperluan'         => 'Test return archive',
            'tgl_pinjam'        => date('Y-m-d H:i:s'),
            'tgl_haruskembali'  => date('Y-m-d H:i:s', strtotime('+7 days')),
            'tgl_transaksi'     => date('Y-m-d H:i:s'),
        ];

        $id = $this->model->insert($data);
        $this->assertNotFalse($id);

        // Test returnArchive
        $result = $this->model->returnArchive($id);
        $this->assertTrue($result);

        // Verify return date is set
        $updated = $this->model->find($id);
        $this->assertNotNull($updated['tgl_pengembalian']);

        // Cleanup
        $this->model->delete($id);
    }
}