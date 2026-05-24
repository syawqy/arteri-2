<?php

declare(strict_types=1);

namespace Tests\App\Controllers\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ApiKeyControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\ArteriSeeder::class;
    protected $basePath  = APPPATH . 'Database';
    protected $namespace = 'App';

    private function adminSession(): array
    {
        return [
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => ['entridata' => 'on'],
            'menu_master' => true,
        ];
    }

    public function testListApiKeysRequiresAdminSession(): void
    {
        $result = $this->get('api/v1/admin/api-keys');
        $result->assertStatus(401);
    }

    public function testGenerateAndRevokeApiKey(): void
    {
        $this->withSession($this->adminSession());

        $create = $this->withHeaders([
            'Content-Type' => 'application/json',
        ])->withBodyFormat('json')->post('api/v1/admin/api-keys', [
            'name'       => 'Integration Test',
            'rate_limit' => 120,
        ]);

        $create->assertStatus(201);
        $body = json_decode($create->getJSON(), true);
        $this->assertTrue($body['success']);
        $this->assertNotEmpty($body['data']['api_key']);
        $keyId = (int) $body['data']['id'];

        $list = $this->get('api/v1/admin/api-keys');
        $list->assertStatus(200);

        $revoke = $this->delete('api/v1/admin/api-keys/' . $keyId);
        $revoke->assertStatus(200);
    }

    public function testNonAdminCannotManageKeys(): void
    {
        $this->withSession([
            'username' => 'user1',
            'tipe'     => 'user',
        ]);

        $this->get('api/v1/admin/api-keys')->assertStatus(403);
    }
}
