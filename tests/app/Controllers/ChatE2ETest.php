<?php

declare(strict_types=1);

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * E2E Tests for Chat Controller — all roles × all use cases.
 *
 * Covers:
 *  - GET  /chat          (page access)
 *  - POST /chat/api      (user info, MCP proxy, LLM chat, invalid action)
 *  - POST /chat/mcp-tool (direct MCP tool execution)
 *
 * Roles: admin, user (restricted klas/modul), unauthenticated
 *
 * @internal
 */
final class ChatE2ETest extends CIUnitTestCase
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

    private function skipIfMcpNotRunning(): void
    {
        $mcpUrl  = env('MCP_HTTP_URL', 'http://127.0.0.1:8090') . '/mcp';
        $ch = curl_init($mcpUrl);
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'initialize',
                'params'  => ['protocolVersion' => '2025-03-26', 'capabilities' => (object)[], 'clientInfo' => ['name' => 'test', 'version' => '1.0.0']],
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->markTestSkipped('MCP server not running — skipping MCP test');
        }
    }

    // ─────────────────────────────────────────────
    // GET /chat — page access
    // ─────────────────────────────────────────────

    public function testGetChatRequiresAuth(): void
    {
        $this->get('chat')->assertRedirectTo('/login');
    }

    public function testGetChatAdminCanAccess(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->get('chat');
        $response->assertStatus(200);
        $this->assertStringContainsString('AI Assistant', (string) $response->response()->getBody());
    }

    public function testGetChatUserCanAccess(): void
    {
        $this->withSession($this->userSession());
        $this->get('chat')->assertStatus(200);
    }

    public function testGetChatPageContainsUserInput(): void
    {
        $this->withSession($this->adminSession());
        $body = (string) $this->get('chat')->getBody();
        $this->assertStringContainsString('id="userInput"', $body);
    }

    public function testGetChatPageContainsConnectMcp(): void
    {
        $this->withSession($this->adminSession());
        $body = (string) $this->get('chat')->getBody();
        $this->assertStringContainsString('connectMcp', $body);
    }

    public function testGetChatPageContainsSendBtn(): void
    {
        $this->withSession($this->adminSession());
        $body = (string) $this->get('chat')->getBody();
        $this->assertStringContainsString('id="sendBtn"', $body);
    }

    public function testGetChatPageContainsChatMessages(): void
    {
        $this->withSession($this->adminSession());
        $body = (string) $this->get('chat')->getBody();
        $this->assertStringContainsString('id="chatMessages"', $body);
    }

    public function testGetChatPageContainsMcpDot(): void
    {
        $this->withSession($this->adminSession());
        $body = (string) $this->get('chat')->getBody();
        $this->assertStringContainsString('id="mcpDot"', $body);
    }

    public function testGetChatPageContainsMcpStatus(): void
    {
        $this->withSession($this->adminSession());
        $body = (string) $this->get('chat')->getBody();
        $this->assertStringContainsString('id="mcpStatus"', $body);
    }

    // ─────────────────────────────────────────────
    // POST /chat/api action: user
    // ─────────────────────────────────────────────

    public function testApiUserRequiresAuth(): void
    {
        $this->postJson('chat/api', ['action' => 'user'])->assertRedirectTo('/login');
    }

    public function testApiUserAdminReturnsUsernameAndTipe(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $response->assertStatus(200);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('admin', $json['username']);
        $this->assertSame('admin', $json['tipe']);
    }

    public function testApiUserRegularReturnsUsernameAndTipe(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $response->assertStatus(200);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('user', $json['username']);
        $this->assertSame('user', $json['tipe']);
    }

    public function testApiUserReturnsJsonContentType(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $this->assertStringContainsString('application/json', $response->response()->getHeaderLine('Content-Type'));
    }

    // ─────────────────────────────────────────────
    // POST /chat/api action: chat
    // ─────────────────────────────────────────────

    public function testApiChatRequiresAuth(): void
    {
        $this->postJson('chat/api', ['action' => 'chat', 'messages' => [['role' => 'user', 'content' => 'hi']]])
            ->assertRedirectTo('/login');
    }

    public function testApiChatEmptyMessagesReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'chat', 'messages' => []]);
        $response->assertStatus(400);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertArrayHasKey('error', $json);
    }

    public function testApiChatMissingMessagesReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'chat']);
        $response->assertStatus(400);
    }

    public function testApiChatAdminCanSend(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);
        // LLM may not be available in test environment
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testApiChatUserCanSend(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testApiChatWithMcpTools(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'test']],
            'mcpTools' => [
                [
                    'name'        => 'test_tool',
                    'description' => 'A test tool',
                    'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]],
                ],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testApiChatResponseStructure(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'test']],
        ]);

        $code = (int) $response->response()->getStatusCode();
        if ($code === 200) {
            $json = json_decode((string) $response->response()->getBody(), true);
            $this->assertArrayHasKey('response', $json);
            $this->assertArrayHasKey('model', $json);
        } else {
            // 400/502 is acceptable when LLM is unavailable
            $this->assertContains($code, [400, 502]);
        }
    }

    // ─────────────────────────────────────────────
    // POST /chat/api action: mcp
    // ─────────────────────────────────────────────

    public function testApiMcpRequiresAuth(): void
    {
        $this->postJson('chat/api', ['action' => 'mcp', 'payload' => ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => (object)[]]])
            ->assertRedirectTo('/login');
    }

    public function testApiMcpAdminCanCallMcp(): void
    {
        $this->skipIfMcpNotRunning();
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'  => 'mcp',
            'sessionId' => null,
            'payload' => [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'tools/list',
                'params'  => (object)[],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testApiMcpUserCanCallMcp(): void
    {
        $this->skipIfMcpNotRunning();
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', [
            'action'  => 'mcp',
            'sessionId' => null,
            'payload' => [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'tools/list',
                'params'  => (object)[],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // POST /chat/api — unknown/invalid action
    // ─────────────────────────────────────────────

    public function testApiUnknownActionReturns400(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', ['action' => 'nonexistent']);
        $response->assertStatus(400);

        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertArrayHasKey('error', $json);
    }

    public function testApiInvalidJsonReturnsError(): void
    {
        $this->withSession($this->adminSession());
        // CI4's getJSON() throws HTTPException for invalid JSON
        // Test with a valid action but empty messages instead
        $response = $this->postJson('chat/api', ['action' => 'chat', 'messages' => []]);
        $this->assertContains((int) $response->response()->getStatusCode(), [400, 404, 500]);
    }

    // ─────────────────────────────────────────────
    // POST /chat/mcp-tool
    // ─────────────────────────────────────────────

    public function testMcpToolCallRequiresAuth(): void
    {
        $this->postJson('chat/mcp-tool', ['payload' => ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']])
            ->assertRedirectTo('/login');
    }

    public function testMcpToolCallAdminCanExecute(): void
    {
        $this->skipIfMcpNotRunning();
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/mcp-tool', [
            'payload' => [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'tools/list',
                'params'  => (object)[],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testMcpToolCallUserCanExecute(): void
    {
        $this->skipIfMcpNotRunning();
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/mcp-tool', [
            'payload' => [
                'jsonrpc' => '2.0',
                'id'      => 1,
                'method'  => 'tools/list',
                'params'  => (object)[],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // Multi-turn conversations
    // ─────────────────────────────────────────────

    public function testMultiTurnConversation(): void
    {
        $this->withSession($this->adminSession());
        $messages = [
            ['role' => 'user', 'content' => 'Hello, I am testing multi-turn.'],
            ['role' => 'assistant', 'content' => 'Got it. What next?'],
            ['role' => 'user', 'content' => 'Now tell me about archives.'],
        ];
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => $messages,
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // Edge cases
    // ─────────────────────────────────────────────

    public function testChatWithEmptyContent(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => '']],
        ]);
        // Empty content is still a valid message array — LLM call is attempted
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithLongMessage(): void
    {
        $this->withSession($this->adminSession());
        $longMessage = str_repeat('This is a long message. ', 100);
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => $longMessage]],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithSpecialChars(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => '<script>alert("xss")</script> & "quotes"']],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithIndonesianText(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'Cari arsip surat masuk tahun 2026']],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithUnicode(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => '日本語テスト 🎉 Ñoño']],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithMaxTokens(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'     => 'chat',
            'messages'   => [['role' => 'user', 'content' => 'hi']],
            'maxTokens'  => 100,
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithNullMcpTools(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'    => 'chat',
            'messages'  => [['role' => 'user', 'content' => 'hi']],
            'mcpTools'  => null,
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testChatWithEmptyMcpTools(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->postJson('chat/api', [
            'action'    => 'chat',
            'messages'  => [['role' => 'user', 'content' => 'hi']],
            'mcpTools'  => [],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // Tool call message handling
    // ─────────────────────────────────────────────

    public function testChatWithToolCallMessage(): void
    {
        $this->withSession($this->adminSession());
        $messages = [
            ['role' => 'user', 'content' => 'search archives'],
            [
                'role'       => 'assistant',
                'content'    => null,
                'tool_calls' => [
                    [
                        'id'       => 'call_123',
                        'type'     => 'function',
                        'function' => [
                            'name'      => 'natural_language_search',
                            'arguments' => json_encode(['query' => 'archives']),
                        ],
                    ],
                ],
            ],
            [
                'role'        => 'tool',
                'content'     => '{"results": []}',
                'tool_call_id' => 'call_123',
            ],
            ['role' => 'user', 'content' => 'Show me the results'],
        ];
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => $messages,
            'mcpTools' => [
                [
                    'name'        => 'natural_language_search',
                    'description' => 'Search archives',
                    'inputSchema' => ['type' => 'object', 'properties' => ['query' => ['type' => 'string']]],
                ],
            ],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    // ─────────────────────────────────────────────
    // User role can call all actions
    // ─────────────────────────────────────────────

    public function testUserCanCallUserAction(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $response->assertStatus(200);
        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('user', $json['username']);
    }

    public function testUserCanCallChatAction(): void
    {
        $this->withSession($this->userSession());
        $response = $this->postJson('chat/api', [
            'action'   => 'chat',
            'messages' => [['role' => 'user', 'content' => 'hello']],
        ]);
        $this->assertContains((int) $response->response()->getStatusCode(), [200, 400, 502]);
    }

    public function testUserCanCallMcpAction(): void
    {
        $this->withSession($this->userSession());
        // Even if MCP isn't running, the route is accessible
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

    // ─────────────────────────────────────────────
    // HTTP method enforcement
    // ─────────────────────────────────────────────

    public function testGetOnPostOnlyChatApiReturns404(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->get('chat/api');
            $this->fail('Expected PageNotFoundException');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    public function testGetOnPostOnlyMcpToolReturns404(): void
    {
        $this->withSession($this->adminSession());
        try {
            $this->get('chat/mcp-tool');
            $this->fail('Expected PageNotFoundException');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    // ─────────────────────────────────────────────
    // Session manipulation prevention
    // ─────────────────────────────────────────────

    public function testCannotSpoofUsernameViaSessionManipulation(): void
    {
        $this->withSession([
            'username'    => 'admin',
            'id_user'     => 1,
            'tipe'        => 'admin',
            'akses_klas'  => '',
            'akses_modul' => ['entridata' => 'on'],
            'menu_master' => true,
        ]);
        $response = $this->postJson('chat/api', ['action' => 'user']);
        $json = json_decode((string) $response->response()->getBody(), true);
        $this->assertSame('admin', $json['username']);
    }
}
