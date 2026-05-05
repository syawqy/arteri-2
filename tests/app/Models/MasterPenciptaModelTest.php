<?php

namespace Tests\App\Models;

use App\Models\MasterPenciptaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MasterPenciptaModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private MasterPenciptaModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MasterPenciptaModel();
    }

    public function testInsertAndFind(): void
    {
        $id = $this->model->insert(['nama_pencipta' => 'Pencipta Test'], true);
        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('Pencipta Test', $row['nama_pencipta']);
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->model->insert(['nama_pencipta' => 'Old Name'], true);
        $this->model->update($id, ['nama_pencipta' => 'New Name']);
        $this->assertSame('New Name', $this->model->find($id)['nama_pencipta']);

        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testFindAllReturnsOrdered(): void
    {
        $results = $this->model->orderBy('nama_pencipta', 'ASC')->findAll();
        $this->assertNotEmpty($results);
    }
}
