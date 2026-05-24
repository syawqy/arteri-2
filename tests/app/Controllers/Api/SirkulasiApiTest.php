<?php

declare(strict_types=1);

namespace Tests\App\Controllers\Api;

use App\Services\ApiKeyService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class SirkulasiApiTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        cache()->clean();

        $service = new ApiKeyService();
        $result  = $service->generate('Sirkulasi Test Key', 'admin', 60);
        $this->apiKey = $result['plain_key'];
    }

    public function testListRequiresApiKey(): void
    {
        $this->get('api/v1/sirkulasi')->assertStatus(401);
    }

    public function testListReturnsPaginatedSuccess(): void
    {
        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/sirkulasi');

        $response->assertStatus(200);

        $body = json_decode($response->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('pagination', $body);
    }

    public function testShowNonExistentReturns404(): void
    {
        $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/sirkulasi/999999')
            ->assertStatus(404);
    }

    public function testCreateWithEmptyBodyReturns400(): void
    {
        $response = $this->withHeaders([
            'X-API-Key' => $this->apiKey,
        ])->post('api/v1/sirkulasi', []);

        $response->assertStatus(400);
    }

    public function testCreateWithMissingRequiredFieldsReturns422(): void
    {
        $response = $this->withHeaders([
            'X-API-Key'    => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->withBodyFormat('json')->post('api/v1/sirkulasi', [
            'noarsip' => 'NA-TEST-001',
            // missing username_peminjam and tgl_haruskembali
        ]);

        $response->assertStatus(422);
    }
}
