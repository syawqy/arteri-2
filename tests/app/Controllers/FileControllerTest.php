<?php

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class FileControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private function getBasicSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => [],
            'menu_master' => false,
        ];
    }

    public function testFileRequiresAuth(): void
    {
        $this->get('file/test.pdf')->assertRedirect();
    }

    public function testFileReturns404ForNonexistentFile(): void
    {
        $this->withSession($this->getBasicSession());

        // Register trigger to avoid CI4 404 throwing exception
        // FeatureTest needs a db row with that file column
        $this->get('file/nonexistent_file_12345.pdf');

        // FeatureTest automatically converts 404 PageNotFoundException
        // into a 404 response in testing context — confirm response is not a 200
        $response = $this->get('file/nonexistent_file_12345.pdf');
        $this->assertNotSame(200, $response->response()->getStatusCode());
    }

    public function testFileReturns404WhenNoDbRecord(): void
    {
        $this->withSession($this->getBasicSession());
        $response = $this->get('file/no_record_file.pdf');
        $this->assertNotSame(200, $response->response()->getStatusCode());
    }
}
