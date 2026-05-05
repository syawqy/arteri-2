<?php

namespace Tests\App\Models;

use App\Models\MasterKodeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class MasterKodeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private MasterKodeModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new MasterKodeModel();
    }

    public function testInsertAndFind(): void
    {
        $id = $this->model->insert([
            'kode'    => 'TST.01',
            'nama'    => 'Test Entry',
            'retensi' => 5,
        ], true);

        $row = $this->model->find($id);
        $this->assertNotNull($row);
        $this->assertSame('TST.01', $row['kode']);
        $this->assertSame('Test Entry', $row['nama']);
        $this->assertSame(5, (int) $row['retensi']);
    }

    public function testUpdateAndDelete(): void
    {
        $id = $this->model->insert([
            'kode'    => 'TST.02',
            'nama'    => 'To Update',
            'retensi' => 1,
        ], true);

        $this->model->update($id, ['nama' => 'Updated Name']);
        $row = $this->model->find($id);
        $this->assertSame('Updated Name', $row['nama']);

        $this->model->delete($id);
        $this->assertNull($this->model->find($id));
    }

    public function testSearchWithKeyword(): void
    {
        $results = $this->model->search('Rekrutmen');
        $this->assertNotEmpty($results);
        $this->assertSame('SDM.01', $results[0]['kode']);
    }

    public function testSearchWithEmptyKeywordReturnsAll(): void
    {
        $results = $this->model->search('');
        $this->assertGreaterThanOrEqual(17, count($results));
    }

    public function testSearchWithKeywordNoMatchReturnsEmpty(): void
    {
        $results = $this->model->search('ZZZZNOTEXIST');
        $this->assertEmpty($results);
    }
}
