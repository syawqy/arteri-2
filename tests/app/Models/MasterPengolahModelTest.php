<?php

namespace Tests\App\Models;

use App\Models\MasterPengolahModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MasterPengolahModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private MasterPengolahModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MasterPengolahModel();
    }

    public function testInsertAndFind(): void
    {
        $id = $this->model->insert(['nama_pengolah' => 'Pengolah Test'], true);
        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('Pengolah Test', $row['nama_pengolah']);
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->model->insert(['nama_pengolah' => 'Old Name'], true);
        $this->model->update($id, ['nama_pengolah' => 'New Name']);
        $this->assertSame('New Name', $this->model->find($id)['nama_pengolah']);

        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testFindAllReturnsOrdered(): void
    {
        $results = $this->model->orderBy('nama_pengolah', 'ASC')->findAll();
        $this->assertNotEmpty($results);
    }
}
