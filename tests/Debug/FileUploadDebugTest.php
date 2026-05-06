<?php
namespace Tests\Debug;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class FileUploadDebugTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testFilesSuperglobalPersistence(): void
    {
        file_put_contents(WRITEPATH . 'debug_upload.pdf', '%PDF-1.4');

        service('superglobals')->setFilesArray([
            'file' => [
                'name'     => 'debug_upload.pdf',
                'tmp_name' => WRITEPATH . 'debug_upload.pdf',
                'type'     => 'application/pdf',
                'size'     => 8,
                'error'    => UPLOAD_ERR_OK,
            ],
        ]);

        // First call (CSRF setup) - does it clear files?
        $this->get('/login');

        // Check files still exist
        $files = service('superglobals')->getFilesArray();
        echo 'Files after GET: ' . var_export(!empty($files), true) . PHP_EOL;
        echo 'Files count: ' . count($files) . PHP_EOL;

        // Now do the actual POST
        $result = $this->post('/arsip', [
            'noarsip'      => 'DEBUG-001',
            'tanggal'      => '2025-06-01',
            'pencipta'     => '1',
            'unitpengolah' => '1',
            'kode'         => '1',
            'uraian'       => 'debug upload test',
            'lokasi'       => '1',
            'media'        => '1',
            'ket'          => 'asli',
            'jumlah'       => '1',
        ]);

        @unlink(WRITEPATH . 'debug_upload.pdf');
        $this->assertTrue(true);
    }
}
