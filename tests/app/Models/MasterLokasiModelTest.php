<?php

namespace Tests\App\Models;

use App\Models\MasterLokasiModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MasterLokasiModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private MasterLokasiModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MasterLokasiModel();
    }

    public function testInsertAndFind(): void
    {
        $id = $this->model->insert(['nama_lokasi' => 'Lokasi Test'], true);
        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('Lokasi Test', $row['nama_lokasi']);
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->model->insert(['nama_lokasi' => 'Old Name'], true);
        $this->model->update($id, ['nama_lokasi' => 'New Name']);
        $this->assertSame('New Name', $this->model->find($id)['nama_lokasi']);

        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testFindAllReturnsOrdered(): void
    {
        $results = $this->model->orderBy('nama_lokasi', 'ASC')->findAll();
        $this->assertNotEmpty($results);
    }
}
