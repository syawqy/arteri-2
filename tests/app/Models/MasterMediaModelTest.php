<?php

namespace Tests\App\Models;

use App\Models\MasterMediaModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MasterMediaModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private MasterMediaModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MasterMediaModel();
    }

    public function testInsertAndFind(): void
    {
        $id = $this->model->insert(['nama_media' => 'Media Test'], true);
        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('Media Test', $row['nama_media']);
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->model->insert(['nama_media' => 'Old Name'], true);
        $this->model->update($id, ['nama_media' => 'New Name']);
        $this->assertSame('New Name', $this->model->find($id)['nama_media']);

        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testFindAllReturnsOrdered(): void
    {
        $results = $this->model->orderBy('nama_media', 'ASC')->findAll();
        $this->assertNotEmpty($results);
    }
}
