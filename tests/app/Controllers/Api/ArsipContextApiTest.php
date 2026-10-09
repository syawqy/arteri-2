<?php

declare(strict_types=1);

namespace Tests\App\Controllers\Api;

use App\Models\ApiKeyModel;
use App\Models\ArsipModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ArsipContextApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private function generateApiKey(): string
    {
        $apiKeyService = new \App\Services\ApiKeyService();
        $res = $apiKeyService->generate('Test Key', 'admin', 100);
        return $res['plain_key'];
    }

    public function testContextEndpointRequiresAuth(): void
    {
        $result = $this->get('api/v1/arsip/1/context');
        $result->assertStatus(401);
    }

    public function testContextEndpointReturnsRiCOJsonLd(): void
    {
        $key = $this->generateApiKey();

        $arsipModel = new ArsipModel();
        $id = $arsipModel->insert([
            'noarsip' => 'TEST-RIC-001',
            'pencipta' => 'Biro Keuangan',
            'unit_pengolah' => 'Bagian Akuntansi',
            'kode' => 'KU.01',
            'uraian' => 'Laporan Pengadaan Sarana Prasarana',
            'tanggal' => '2026-01-10',
            'ket' => 'Asli',
            'jumlah' => 1,
            'nobox' => 'B01',
            'lokasi' => 'Rak 1',
            'media' => 'Kertas',
            'username' => 'admin',
        ]);

        $this->assertIsInt($id);

        $result = $this->withHeaders([
            'X-API-Key' => $key,
        ])->get("api/v1/arsip/{$id}/context");

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('seed', $body['data']);
        $this->assertArrayHasKey('related', $body['data']);
        $this->assertArrayHasKey('ric_jsonld', $body['data']);
        $this->assertEquals('https://www.ica.org/standards/RiC/ontology#', $body['data']['ric_jsonld']['@context']['ric']);
    }
}
