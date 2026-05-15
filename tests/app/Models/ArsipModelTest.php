<?php

namespace Tests\App\Models;

use App\Models\ArsipModel;
use App\Models\MasterKodeModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\DatabaseTestTrait;

/**
 * Unit tests for ArsipModel
 * 
 * @group Model
 */
class ArsipModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $seed = 'Tests\Support\Database\Seeds\ArteriSeeder';

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ArsipModel();
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
        $results = $this->model->search('', [], 10);
        $this->assertIsArray($results);
    }

    /**
     * Test search with keywords returns filtered results
     */
    public function testSearchWithKeywords(): void
    {
        $results = $this->model->search('arsip', [], 10);
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
     * Test getDetail returns array or null
     */
    public function testGetDetailReturnsArrayOrNull(): void
    {
        // Get first ID from search
        $results = $this->model->search('', [], 1);
        if (! empty($results)) {
            $id = $results[0]['id'] ?? null;
            if ($id !== null) {
                $detail = $this->model->getDetail($id);
                $this->assertIsArray($detail);
            }
        }
        // Test with non-existent ID
        $detail = $this->model->getDetail(999999);
        $this->assertNull($detail);
    }

    /**
     * Test that allowed fields are correctly set
     */
    public function testAllowedFields(): void
    {
        $allowedFields = $this->model->allowedFields;
        
        $this->assertContains('noarsip', $allowedFields);
        $this->assertContains('kode', $allowedFields);
        $this->assertContains('uraian', $allowedFields);
        $this->assertContains('tanggal', $allowedFields);
    }

    /**
     * Test table name is correctly set
     */
    public function testTableName(): void
    {
        $this->assertEquals('data_arsip', $this->model->table);
    }

    /**
     * Test that primary key is id
     */
    public function testPrimaryKey(): void
    {
        $this->assertEquals('id', $this->model->primaryKey);
    }

    /**
     * Test insert method works
     */
    public function testInsert(): void
    {
        $data = [
            'noarsip'       => 'TEST-' . time(),
            'tanggal'       => date('Y-m-d'),
            'pencipta'      => 1,
            'unit_pengolah' => 1,
            'kode'          => 1,
            'uraian'        => 'Test arsip for unit testing',
            'lokasi'        => 1,
            'media'         => 1,
            'ket'           => 'asli',
            'jumlah'        => 1,
            'username'      => 'test_user',
        ];

        $result = $this->model->insert($data);
        $this->assertNotFalse($result);

        // Cleanup
        if ($result) {
            $this->model->delete($result);
        }
    }

    /**
     * Test update method works
     */
    public function testUpdate(): void
    {
        // First insert
        $data = [
            'noarsip'       => 'UPDATE-' . time(),
            'tanggal'       => date('Y-m-d'),
            'pencipta'      => 1,
            'unit_pengolah' => 1,
            'kode'          => 1,
            'uraian'        => 'Test update arsip',
            'lokasi'        => 1,
            'media'         => 1,
            'ket'           => 'asli',
            'jumlah'        => 1,
            'username'      => 'test_user',
        ];

        $id = $this->model->insert($data);
        $this->assertNotFalse($id);

        // Update
        $updateResult = $this->model->update($id, ['uraian' => 'Updated arsip']);
        $this->assertTrue($updateResult);

        // Verify update
        $updated = $this->model->find($id);
        $this->assertEquals('Updated arsip', $updated['uraian']);

        // Cleanup
        $this->model->delete($id);
    }

    /**
     * Test delete method works
     */
    public function testDelete(): void
    {
        // First insert
        $data = [
            'noarsip'       => 'DELETE-' . time(),
            'tanggal'       => date('Y-m-d'),
            'pencipta'      => 1,
            'unit_pengolah' => 1,
            'kode'          => 1,
            'uraian'        => 'Test delete arsip',
            'lokasi'        => 1,
            'media'         => 1,
            'ket'           => 'asli',
            'jumlah'        => 1,
            'username'      => 'test_user',
        ];

        $id = $this->model->insert($data);
        $this->assertNotFalse($id);

        // Delete
        $deleteResult = $this->model->delete($id);
        $this->assertTrue($deleteResult);

        // Verify deletion
        $deleted = $this->model->find($id);
        $this->assertNull($deleted);
    }
}