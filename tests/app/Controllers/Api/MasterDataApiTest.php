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
final class MasterDataApiTest extends CIUnitTestCase
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
        $result  = $service->generate('Master Test Key', 'admin', 60);
        $this->apiKey = $result['plain_key'];
    }

    public function testListRequiresApiKey(): void
    {
        $this->get('api/v1/master/kode')->assertStatus(401);
    }

    public function testListKodeReturnsSeedData(): void
    {
        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/master/kode');

        $response->assertStatus(200);

        $body = json_decode($response->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertNotEmpty($body['data']);
    }

    public function testListInvalidTypeReturns400(): void
    {
        $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/master/invalidtype')
            ->assertStatus(400);
    }

    public function testShowReturnsSingleRecord(): void
    {
        $response = $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/master/kode/1');

        $response->assertStatus(200);

        $body = json_decode($response->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('kode', $body['data']);
    }

    public function testShowNonExistentReturns404(): void
    {
        $this->withHeaders(['X-API-Key' => $this->apiKey])
            ->get('api/v1/master/kode/999999')
            ->assertStatus(404);
    }
}
