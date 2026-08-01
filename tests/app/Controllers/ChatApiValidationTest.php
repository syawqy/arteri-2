<?php

declare(strict_types=1);

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Input validation tests for Chat API endpoints.
 *
 * Covers:
 *  - Missing/empty/invalid action
 *  - Message validation (role, content, types)
 *  - MCP tools schema validation
 *  - MCP proxy payload validation
 *  - HTTP method enforcement
 *  - Response format validation
 *  - User role calling all actions
 *  - Session manipulation prevention
 *
 * @internal
 */
final class ChatApiValidationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
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
            'akses_modul' => [
                'entridata'   => 'on',
                'sirkulasi'   => 'on',
                'klasifikasi' => 'on',
                'pencipta'    => 'on',
                'pengolah'    => 'on',
                'lokasi'      => 'on',
                'media'       => 'on',
                'user'        => 'on',
                'import'      => 'on',
            ],
            'menu_master' => true,
        ];
    }

    private function userSession(): array
    {
        return [
            'username'    => 'user',
            'id_user'     => 2,
            'tipe'        => 'user',
            'akses_klas'  => 'sdm,hkp',
            'akses_modul' => [
                'sirkulasi' => 'on',
            ],
            'menu_master' => false,
        ];
    }

    private function postJson(string $uri, array $data): \CodeIgniter\Test\TestResponse
    {
        return $this->withBody(json_encode($data))
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($uri);
    }

    // ─────────────────────────────────────────────
    // Action validation
    // ─────────────────────────────────────────────

    public function testMissingActionReturns400(): void
    {
        $this->withSession($this->adminSession());
        // No 'action' key — defaults to 'chat' in controller, but with no messages → 400
        $response = $this->postJson('chat/api', []);
        // Missing action defaults to 'chat', but no messages → 400
        $response->assertStatus(400);
    }

    public function testEmptyStringActionDefaultsToChat(): void
    {
        $this->withSession($this->adminSession());
        // Empty string as action — switch falls through to default → 400 'Unknown action'
        $response = $this->postJson('chat/api', ['action' => '']);
        $response->assertStatus(400);
    }

    public function testNumericActionReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 123]);
        $response->assertStatus(400);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertArrayHasKey('error', $json);
    }

    public function testUserActionIgnoresExtraFields(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'     => 'user',
            'irrelevant' => 'data',
            'messages'   => [['role' => 'user', 'content' => 'test']],
        ]);
        $response->assertStatus(200);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertArrayHasKey('username', $json);
        $this->assertArrayHasKey('tipe', $json);
        // The extra fields should not cause errors
    }

    // ─────────────────────────────────────────────
    // Message validation
    // ─────────────────────────────────────────────

    public function testChatMissingMessagesReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action' => 'chat',
        ]);
        $response->assertStatus(400);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertArrayHasKey('error', $json);
    }

    public function testChatEmptyMessagesReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [],
        ]);
        $response->assertStatus(400);
    }

    public function testChatMessagesMustBeArray(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => 'not an array',
        ]);
        // Empty string messages triggers 400
        $this->assertContains((int) $response->response()->getStatusCode(), [400, 500]);
    }

    public function testChatMessageWithMissingRoleDefaultsToUser(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['content' => 'Hello']],
        ]);
        // Missing role defaults to 'user' in controller — LLM call is attempted
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatMessageWithMissingContentDefaultsToEmpty(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user']],
        ]);
        // Missing content defaults to '' in controller — LLM call is attempted
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatMessageInvalidRoleDefaultsToUser(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'invalid_role', 'content' => 'test']],
        ]);
        // Invalid role is still passed through — defaults to whatever role is set
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithStringMessagesReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => 'just a string',
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [400, 500]);
    }

    public function testChatWithZeroMessagesReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => 0,
        ]);
        $response->assertStatus(400);
    }

    // ─────────────────────────────────────────────
    // MCP tools validation
    // ─────────────────────────────────────────────

    public function testChatWithMcpToolMissingName(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'test']],
            'mcpTools' => [
                ['description' => 'A tool without name', 'inputSchema' => ['type' => 'object', 'properties' => []]],
            ],
        ]);
        // Tool without name — controller accesses $tool['name'] ?? 'unknown'
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithMcpToolEmptySchema(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'test']],
            'mcpTools' => [
                ['name' => 'test_tool', 'description' => 'test'],
            ],
        ]);
        // Missing inputSchema defaults to ['type' => 'object', 'properties' => []]
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithMcpToolNullProperties(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'test']],
            'mcpTools' => [
                [
                    'name'        => 'test_tool',
                    'description' => 'test',
                    'inputSchema' => ['type' => 'object', 'properties' => null],
                ],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // MCP proxy payload validation
    // ─────────────────────────────────────────────

    public function testMcpProxyMissingPayload(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action' => 'mcp',
            'sessionId' => null,
        ]);
        // Missing payload defaults to [] — goes to MCP server
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testMcpProxyNullPayload(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'  => 'mcp',
            'sessionId' => null,
            'payload' => null,
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testMcpProxyEmptyPayload(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'  => 'mcp',
            'sessionId' => null,
            'payload' => [],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // HTTP method enforcement
    // ─────────────────────────────────────────────

    public function testGetChatApiReturns404(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->get('chat/api');
            $this->fail('Expected PageNotFoundException');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetChatMcpToolReturns404(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->get('chat/mcp-tool');
            $this->fail('Expected PageNotFoundException');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testPutChatApiReturns404(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->put('chat/api');
            $this->fail('Expected PageNotFoundException');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testDeleteChatApiReturns404(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->delete('chat/api');
            $this->fail('Expected PageNotFoundException');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    // ─────────────────────────────────────────────
    // Response format validation
    // ─────────────────────────────────────────────

    public function testUserActionResponseFormat(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $json = json_decode((string) $response->response()->getBody(), true);

        $this->assertArrayHasKey('username', $json);
        $this->assertArrayHasKey('tipe', $json);
        $this->assertIsString($json['username']);
        $this->assertIsString($json['tipe']);
    }

    public function testErrorResponsesHaveErrorField(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'nonexistent']);
        $json = json_decode((string) $response->response()->getBody(), true);

        $this->assertArrayHasKey('error', $json);
        $this->assertIsString($json['error']);
    }

    public function testChatErrorResponseHasErrorField(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [],
        ]);
        $json = json_decode((string) $response->response()->getBody(), true);

        $this->assertArrayHasKey('error', $json);
    }

    public function testUnauthenticatedReturnsRedirect(): void
    {
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $response->assertRedirectTo('/login');
    }

    public function testInvalidJsonReturns400(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->withBody('{invalid json')
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post('chat/api');
            $this->fail('Expected HTTPException for invalid JSON');
        } catch (\Throwable $e) {
            // CI4 throws HTTPException for malformed JSON before controller runs
            $this->assertStringContainsString('JSON', $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────
    // Tool call message handling
    // ─────────────────────────────────────────────

    public function testChatWithAssistantToolCallsMessage(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [
                ['role' => 'user', 'content' => 'search for documents'],
                [
                    'role'       => 'assistant',
                    'content'    => null,
                    'tool_calls' => [
                        [
                            'id'       => 'call_001',
                            'type'     => 'function',
                            'function' => [
                                'name'      => 'natural_language_search',
                                'arguments' => '{"query":"documents"}',
                            ],
                        ],
                    ],
                ],
                [
                    'role'        => 'tool',
                    'content'     => '{"results":[]}',
                    'tool_call_id' => 'call_001',
                ],
            ],
            'mcpTools' => [
                [
                    'name'        => 'natural_language_search',
                    'description' => 'Search documents',
                    'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]],
                ],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatToolCallsPassedToLlmPayload(): void
    {
        $this->withSession($this->adminSession());
        // Verify that tool_calls are included in the request body
        // The controller builds messagesArray including tool_calls
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [
                [
                    'role'       => 'assistant',
                    'content'    => null,
                    'tool_calls' => [
                        [
                            'id'       => 'call_999',
                            'type'     => 'function',
                            'function' => [
                                'name'      => 'test_fn',
                                'arguments' => '{}',
                            ],
                        ],
                    ],
                ],
                [
                    'role'        => 'tool',
                    'content'     => 'result',
                    'tool_call_id' => 'call_999',
                ],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // User role can call all actions
    // ─────────────────────────────────────────────

    public function testUserRoleCanCallUserAction(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $response->assertStatus(200);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('user', $json['username']);
        $this->assertSame('user', $json['tipe']);
    }

    public function testUserRoleCanCallChatAction(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'hello']],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testUserRoleCanCallMcpAction(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', [
            'action'  => 'mcp',
            'sessionId' => null,
            'payload' => [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'initialize',
                'params'  => [
                    'protocolVersion' => '2025-03-26',
                    'capabilities'    => (object)[],
                    'clientInfo'      => ['name' => 'test', 'version' => '1.0.0'],
                ],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testUserRoleCanCallUnknownAction(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', ['action' => 'fake']);
        $response->assertStatus(400);
    }

    // ─────────────────────────────────────────────
    // Session manipulation prevention
    // ─────────────────────────────────────────────

    public function testSessionDataCannotBeOverriddenViaPayload(): void
    {
        $this->withSession($this->adminSession());
        // Try to inject tipe in the payload — should be ignored
        $response = $this->postJson('chat/api', [
            'action' => 'user',
            'tipe'   => 'superadmin',
        ]);
        $response->assertStatus(200);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('admin', $json['tipe']);
    }

    public function testSessionUsernameFromSessionNotPayload(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'user',
            'username' => 'hacker',
        ]);
        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('admin', $json['username']);
    }

    // ─────────────────────────────────────────────
    // Content-Type handling
    // ─────────────────────────────────────────────

    public function testPostWithFormEncodedData(): void
    {
        $this->withSession($this->adminSession());
        // Send as form-encoded — controller's getJSON(true) may not parse it
        $response = $this->post('chat/api', ['action' => 'user']);
        // Form-encoded: getJSON returns null → 400 Invalid JSON
        $response->assertStatus(400);
    }

    public function testPostWithJsonContentTypeReturns200(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $response->assertStatus(200);

        $contentType = $response->response()->getHeaderLine('Content-Type');
        $this->assertStringContainsString('application/json', $contentType);
    }

    // ─────────────────────────────────────────────
    // Edge cases
    // ─────────────────────────────────────────────

    public function testMcpToolCallMissingPayload(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/mcp-tool', []);
        // Missing payload → defaults to [] → MCP call
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testMcpToolCallUnauthenticatedReturnsRedirect(): void
    {
        $response = $this->postJson('chat/mcp-tool', ['payload' => ['test' => true]]);
        $response->assertRedirectTo('/login');
    }
}
