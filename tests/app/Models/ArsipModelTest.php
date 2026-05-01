<?php

namespace Tests\App\Models;

use App\Models\ArsipModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ArsipModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private ArsipModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new ArsipModel();
    }

    public function testSearchWithEmptyKeywordReturnsResults(): void
    {
        // NOTE: search() runs DATE_ADD/CURDATE which are MySQL-specific.
        // On SQLite these tests will be skipped; run against MySQL to exercise them.
        $this->skipIfNotMysql();

        $results = $this->model->search('', [], 20, 0);

        $this->assertIsArray($results);
    }

    public function testSearchCountReturnsInt(): void
    {
        $this->skipIfNotMysql();

        $count = $this->model->searchCount('');

        $this->assertIsInt($count);
    }

    public function testGetDetailReturnsNullForNonexistentId(): void
    {
        $this->skipIfNotMysql();

        $result = $this->model->getDetail(99999);

        $this->assertNull($result);
    }

    private function skipIfNotMysql(): void
    {
        $db = \Config\Database::connect();
        if ($db->DBDriver === 'SQLite3') {
            $this->markTestSkipped(
                'ArsipModel uses MySQL functions (DATE_ADD, CURDATE). Run against a MySQL test DB.'
            );
        }
    }
}
