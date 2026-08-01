<?php

declare(strict_types=1);

namespace Tests\App\Controllers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * View rendering tests for the Chat page.
 *
 * Covers:
 *  - Menu link /chat for both admin and user
 *  - Page title "AI Assistant"
 *  - HTML element IDs (chatMessages, userInput, sendBtn, mcpDot, mcpStatus)
 *  - JavaScript functions (connectMcp, tools/list, callLlm, addMsg, renderMd, etc.)
 *  - Same-origin /mcp-api usage
 *  - CSRF handling (X-CSRF-TOKEN in JS)
 *  - State variables in JS
 *  - Status indicator elements
 *  - Both roles see same structure
 *  - Unauthorized redirects
 *  - HTML layout structure
 *
 * @internal
 */
final class ChatViewTest extends CIUnitTestCase
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

    private function getChatPage(array $session): string
    {
        $this->withSession($session);
        return (string) $this->get('chat')->getBody();
    }

    // ─────────────────────────────────────────────
    // Auth / access
    // ─────────────────────────────────────────────

    public function testUnauthorizedRedirectsToLogin(): void
    {
        $this->get('chat')->assertRedirectTo('/login');
    }

    public function testAfterLogoutRedirectsToLogin(): void
    {
        // Simulating that a logged-out user hits /chat
        // The filter would redirect to /login
        $this->get('chat')->assertRedirectTo('/login');
    }

    // ─────────────────────────────────────────────
    // Page title
    // ─────────────────────────────────────────────

    public function testPageTitleContainsAiAssistant(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('AI Assistant', $body);
    }

    public function testPageTitleInHtmlHead(): void
    {
        $this->withSession($this->adminSession());
        $response = $this->get('chat');
        // The layout extends layout/main which has <title>ARTERI - {title}</title>
        // The chat view sets 'title' => 'AI Assistant'
        $this->assertStringContainsString('AI Assistant', (string) $response->getBody());
    }

    // ─────────────────────────────────────────────
    // HTML element IDs
    // ─────────────────────────────────────────────

    public function testPageContainsChatMessagesDiv(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('id="chatMessages"', $body);
    }

    public function testPageContainsUserInputTextarea(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('id="userInput"', $body);
        $this->assertStringContainsString('<textarea', $body);
    }

    public function testPageContainsSendBtn(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('id="sendBtn"', $body);
    }

    public function testPageContainsMcpDot(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('id="mcpDot"', $body);
    }

    public function testPageContainsMcpStatus(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('id="mcpStatus"', $body);
    }

    public function testPageContainsChatWelcome(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('id="chatWelcome"', $body);
    }

    // ─────────────────────────────────────────────
    // JavaScript functions
    // ─────────────────────────────────────────────

    public function testPageContainsConnectMcpJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('connectMcp', $body);
    }

    public function testPageContainsToolsListJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('tools/list', $body);
    }

    public function testPageContainsCallLlmJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('callLlm', $body);
    }

    public function testPageContainsAddMsgJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('addMsg', $body);
    }

    public function testPageContainsRenderMdJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('renderMd', $body);
    }

    public function testPageContainsAddToolBoxJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('addToolBox', $body);
    }

    public function testPageContainsUpdateToolBoxJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('updateToolBox', $body);
    }

    public function testPageContainsEscapeHtmlJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('escapeHtml', $body);
    }

    // ─────────────────────────────────────────────
    // Same-origin /mcp-api (not cross-origin)
    // ─────────────────────────────────────────────

    public function testPageUsesSameOriginMcpApi(): void
    {
        $body = $this->getChatPage($this->adminSession());
        // The fetch calls use /chat/api (same-origin), NOT an external URL
        $this->assertStringContainsString("'/chat/api'", $body);
    }

    // ─────────────────────────────────────────────
    // CSRF handling
    // ─────────────────────────────────────────────

    public function testPageHasCsrfTokenInJs(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('X-CSRF-TOKEN', $body);
    }

    public function testPageHasCsrfHashVariable(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('CSRF_HASH', $body);
    }

    // ─────────────────────────────────────────────
    // State variables
    // ─────────────────────────────────────────────

    public function testPageHasMcpConnectedState(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('mcpConnected', $body);
    }

    public function testPageHasMcpSessionIdState(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('mcpSessionId', $body);
    }

    public function testPageHasMcpToolsState(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('mcpTools', $body);
    }

    public function testPageHasChatHistoryState(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('chatHistory', $body);
    }

    public function testPageHasIsGeneratingState(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('isGenerating', $body);
    }

    // ─────────────────────────────────────────────
    // Both roles see same structure
    // ─────────────────────────────────────────────

    public function testAdminAndUserSeeSameChatElements(): void
    {
        $adminBody = $this->getChatPage($this->adminSession());
        $userBody  = $this->getChatPage($this->userSession());

        $this->assertStringContainsString('id="chatMessages"', $adminBody);
        $this->assertStringContainsString('id="chatMessages"', $userBody);

        $this->assertStringContainsString('id="userInput"', $adminBody);
        $this->assertStringContainsString('id="userInput"', $userBody);

        $this->assertStringContainsString('id="sendBtn"', $adminBody);
        $this->assertStringContainsString('id="sendBtn"', $userBody);

        $this->assertStringContainsString('connectMcp', $adminBody);
        $this->assertStringContainsString('connectMcp', $userBody);
    }

    // ─────────────────────────────────────────────
    // HTML layout structure
    // ─────────────────────────────────────────────

    public function testPageHasDoctype(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('<!DOCTYPE html>', $body);
    }

    public function testPageHasHtmlTag(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('<html', $body);
    }

    public function testPageHasScriptTag(): void
    {
        $body = $this->getChatPage($this->adminSession());
        $this->assertStringContainsString('<script>', $body);
    }
}
