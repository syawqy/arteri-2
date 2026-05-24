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
final class ApiAuthenticationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private ?string $plainApiKey = null;

    protected function setUp(): void
    {
        parent::setUp();

        cache()->clean();

        $service = new ApiKeyService();
        $result  = $service->generate('Test Key', 'admin', 60);
        $this->plainApiKey = $result['plain_key'];
    }

    public function testArsipEndpointRejectsMissingApiKey(): void
    {
        $this->get('api/v1/arsip')->assertStatus(401);
    }

    public function testArsipEndpointRejectsInvalidApiKey(): void
    {
        $this->withHeaders(['X-API-Key' => 'invalid-key'])
            ->get('api/v1/arsip')
            ->assertStatus(401);
    }

    public function testArsipEndpointAcceptsValidApiKey(): void
    {
        $response = $this->withHeaders(['X-API-Key' => $this->plainApiKey])
            ->get('api/v1/arsip');

        $response->assertStatus(200);

        $body = json_decode($response->getJSON(), true);
        $this->assertTrue($body['success']);
    }

    public function testRevokedApiKeyIsRejected(): void
    {
        $service = new ApiKeyService();
        $keys    = $service->listKeys();
        $id      = (int) $keys[0]['id'];
        $service->revoke($id);

        $this->withHeaders(['X-API-Key' => $this->plainApiKey])
            ->get('api/v1/arsip')
            ->assertStatus(401);
    }

    public function testOpenApiSpecIsPublic(): void
    {
        $this->get('api/v1/openapi.json')->assertStatus(200);
    }

    public function testRateLimitExceededReturns429(): void
    {
        $service = new ApiKeyService();
        $result  = $service->generate('Low Limit Key', 'admin', 2);
        $key     = $result['plain_key'];

        // Within one minute window, 3rd call should hit the throttle (limit=2/min).
        $this->withHeaders(['X-API-Key' => $key])->get('api/v1/arsip')->assertStatus(200);
        $this->withHeaders(['X-API-Key' => $key])->get('api/v1/arsip')->assertStatus(200);

        $third = $this->withHeaders(['X-API-Key' => $key])->get('api/v1/arsip');
        $third->assertStatus(429);

        $body = json_decode($third->getJSON(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Rate limit', $body['message']);
    }

    public function testExpiredApiKeyIsRejected(): void
    {
        $service = new ApiKeyService();
        $past    = date('Y-m-d H:i:s', strtotime('-1 day'));
        $result  = $service->generate('Expired Key', 'admin', 60, $past);
        $key     = $result['plain_key'];

        $this->withHeaders(['X-API-Key' => $key])
            ->get('api/v1/arsip')
            ->assertStatus(401);
    }
}
