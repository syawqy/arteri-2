<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ChatSessionModel;
use App\Models\ChatMessageModel;
use CodeIgniter\HTTP\ResponseInterface;

class Chat extends BaseController
{
    private ChatSessionModel $sessionModel;
    private ChatMessageModel $messageModel;

    public function __construct()
    {
        $this->sessionModel  = new ChatSessionModel();
        $this->messageModel  = new ChatMessageModel();
    }

    /**
     * Chat page — requires login
     */
    public function index(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        if (! session('username')) {
            return redirect()->to('/login');
        }

        return view('chat/index', [
            'title'    => 'AI Assistant',
            'username' => session('username'),
        ]);
    }

    /**
     * Chat API — proxies MCP + LLM with user identity
     */
    public function api(): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $username = session('username');
        $input    = $this->request->getJSON(true);

        if (! $input) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid JSON']);
        }

        $action = $input['action'] ?? 'chat';

        switch ($action) {
            case 'user':
                return $this->response->setJSON([
                    'username' => $username,
                    'tipe'     => session('tipe') ?? 'user',
                ]);

            case 'mcp':
                return $this->mcpProxy($input, $username);

            case 'chat':
                return $this->chatApi($input, $username);

            default:
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Unknown action']);
        }
    }

    // ═══════════════════════════════════════════════
    //  SESSION MANAGEMENT
    // ═══════════════════════════════════════════════

    /**
     * List all sessions for current user
     * GET /chat/sessions
     */
    public function sessions(): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $sessions = $this->sessionModel->getSessionsForUser(session('username'));
        return $this->response->setJSON(['sessions' => $sessions]);
    }

    /**
     * Create a new session
     * POST /chat/sessions
     * Body: { title?: string }
     */
    public function createSession(): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $input = $this->request->getJSON(true);
        $title = ($input['title'] ?? 'New Chat') ?: 'New Chat';

        $id = $this->sessionModel->createSession(session('username'), $title);

        return $this->response->setJSON([
            'id'        => $id,
            'title'     => $title,
            'user_id'   => session('username'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Load messages for a session
     * GET /chat/sessions/{id}
     */
    public function loadSession(int $id): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        if (! $this->sessionModel->userOwns($id, session('username'))) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session not found']);
        }

        $session  = $this->sessionModel->find($id);
        $messages = $this->messageModel->getMessages($id);

        // Convert to frontend format
        $formatted = [];
        foreach ($messages as $msg) {
            $entry = [
                'id'        => $msg['id'],
                'role'      => $msg['role'],
                'content'   => $msg['content'],
                'created_at' => $msg['created_at'],
            ];

            if ($msg['tool_calls']) {
                $entry['tool_calls'] = json_decode($msg['tool_calls'], true);
            }
            if ($msg['tool_call_id']) {
                $entry['tool_call_id'] = $msg['tool_call_id'];
            }
            if ($msg['tool_name']) {
                $entry['tool_name'] = $msg['tool_name'];
            }

            $formatted[] = $entry;
        }

        return $this->response->setJSON([
            'session'  => $session,
            'messages' => $formatted,
        ]);
    }

    /**
     * Delete a session
     * DELETE /chat/sessions/{id}
     */
    public function deleteSession(int $id): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $deleted = $this->sessionModel->deleteSession($id, session('username'));

        return $this->response->setJSON([
            'deleted' => $deleted,
        ]);
    }

    /**
     * Update session title
     * PUT /chat/sessions/{id}
     * Body: { title: string }
     */
    public function updateSession(int $id): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        if (! $this->sessionModel->userOwns($id, session('username'))) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session not found']);
        }

        $input = $this->request->getJSON(true);
        $title = $input['title'] ?? 'Untitled';

        $this->sessionModel->updateTitle($id, $title);

        return $this->response->setJSON(['updated' => true, 'title' => $title]);
    }

    // ═══════════════════════════════════════════════
    //  MCP PROXY
    // ═══════════════════════════════════════════════

    private function mcpProxy(array $input, string $username): ResponseInterface
    {
        $mcpUrl = env('MCP_HTTP_URL', 'http://127.0.0.1:8090') . '/mcp';
        $payload = $input['payload'] ?? [];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $mcpUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HEADER         => true,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                'Accept: application/json, text/event-stream',
                'X-Username: ' . $username,
                ! empty($input['sessionId']) ? 'Mcp-Session-Id: ' . $input['sessionId'] : null,
            ]),
        ]);

        $fullResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $parts = explode("\r\n\r\n", $fullResponse, 2);
        $headerBlock = $parts[0] ?? '';
        $body = $parts[1] ?? '';

        $sessionId = null;
        if (preg_match('/Mcp-Session-Id:\s*(\S+)/i', $headerBlock, $m)) {
            $sessionId = $m[1];
        }

        // Save tool result to DB if chatSessionId provided
        $chatSessionId = $input['chatSessionId'] ?? null;
        $toolName     = $input['toolName'] ?? null;
        $toolCallId   = $input['toolCallId'] ?? null;
        if ($chatSessionId && $toolName && $toolCallId && ($payload['method'] ?? '') === 'tools/call') {
            $this->messageModel->saveToolResult(
                (int) $chatSessionId,
                $toolCallId,
                $toolName,
                $body
            );
        }

        return $this->response->setStatusCode($httpCode)
            ->setHeader('Mcp-Session-Id', $sessionId ?? '')
            ->setJSON(json_decode($body, true));
    }

    // ═══════════════════════════════════════════════
    //  LLM CHAT API (with DB persistence)
    // ═══════════════════════════════════════════════

    /**
     * Chat API — call LLM with MCP tools, optionally save to DB
     */
    private function chatApi(array $input, string $username): ResponseInterface
    {
        $messages      = $input['messages'] ?? [];
        $mcpTools      = $input['mcpTools'] ?? [];
        $mcpSessionId  = $input['mcpSessionId'] ?? '';
        $chatSessionId = $input['chatSessionId'] ?? null;

        if (! is_array($messages) || empty($messages)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No messages']);
        }

        // Save user message to DB if chatSessionId provided
        if ($chatSessionId && $this->sessionModel->userOwns((int) $chatSessionId, $username)) {
            $lastUserMsg = null;
            foreach (array_reverse($messages) as $msg) {
                if (($msg['role'] ?? '') === 'user' && ! empty($msg['content'])) {
                    $lastUserMsg = $msg['content'];
                    break;
                }
            }
            if ($lastUserMsg) {
                // Check if this user message was already saved (prevent duplicates)
                $existing = $this->messageModel->where('session_id', $chatSessionId)
                    ->where('role', 'user')
                    ->where('content', $lastUserMsg)
                    ->orderBy('created_at', 'DESC')
                    ->limit(1)
                    ->countAllResults();

                if ($existing === 0) {
                    $this->messageModel->saveUserMessage((int) $chatSessionId, $lastUserMsg);

                    // Auto-title from first message
                    $session = $this->sessionModel->find($chatSessionId);
                    if ($session && $session['title'] === 'New Chat') {
                        $title = mb_substr(trim($lastUserMsg), 0, 50);
                        $this->sessionModel->updateTitle((int) $chatSessionId, $title);
                    }
                }
            }
        }

        // Load .env for LLM config
        $this->loadEnv();

        $llmBaseUrl = getenv('LLM_BASE_URL') ?: 'https://ai.sumopod.com/v1';
        $llmApiKey  = getenv('LLM_API_KEY') ?: '';
        $llmModel   = getenv('LLM_MODEL') ?: 'gpt-4.1-nano';

        // Build system prompt with username
        $systemPrompt = $this->buildSystemPrompt($mcpTools, $username);

        // Build messages array
        $messagesArray = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($messages as $msg) {
            $entry = [
                'role'    => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? '',
            ];
            if (! empty($msg['tool_calls'])) {
                $entry['tool_calls'] = $msg['tool_calls'];
            }
            if (! empty($msg['tool_call_id'])) {
                $entry['tool_call_id'] = $msg['tool_call_id'];
            }
            $messagesArray[] = $entry;
        }

        // Build tools for OpenAI function calling
        $tools = [];
        foreach ($mcpTools as $tool) {
            $schema = $tool['inputSchema'] ?? ['type' => 'object', 'properties' => []];
            if (isset($schema['properties']) && empty($schema['properties'])) {
                $schema['properties'] = (object) [];
            }
            $tools[] = [
                'type'     => 'function',
                'function' => [
                    'name'       => $tool['name'] ?? 'unknown',
                    'description' => $tool['description'] ?? '',
                    'parameters' => $schema,
                ],
            ];
        }

        $payload = [
            'model'       => $llmModel,
            'messages'    => $messagesArray,
            'max_tokens'  => $input['maxTokens'] ?? 4096,
            'temperature' => $input['temperature'] ?? 0.3,
        ];
        if (! empty($tools)) {
            $payload['tools'] = $tools;
        }

        // Call LLM
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $llmBaseUrl . '/chat/completions',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $llmApiKey,
            ],
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'LLM API error: ' . $curlError,
            ]);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'LLM API error (HTTP ' . $httpCode . '): '
                    . ($errorData['error']['message'] ?? $response),
            ]);
        }

        $llmResponse = json_decode($response, true);
        if (empty($llmResponse['choices'])) {
            return $this->response->setStatusCode(502)->setJSON([
                'error' => 'Invalid LLM response',
            ]);
        }

        $choice      = $llmResponse['choices'][0];
        $assistantMsg = $choice['message'];

        // Save assistant response to DB
        if ($chatSessionId && $this->sessionModel->userOwns((int) $chatSessionId, $username)) {
            if (! empty($assistantMsg['tool_calls'])) {
                $this->messageModel->saveAssistantToolCalls(
                    (int) $chatSessionId,
                    $assistantMsg['content'] ?? null,
                    $assistantMsg['tool_calls'],
                    $llmModel
                );
            } else {
                $this->messageModel->saveAssistantMessage(
                    (int) $chatSessionId,
                    $assistantMsg['content'] ?? '',
                    $llmModel,
                    $llmResponse['usage'] ?? null
                );
            }

            // Touch updated_at on session
            $db = \Config\Database::connect();
            $db->table('chat_sessions')
                ->where('id', (int) $chatSessionId)
                ->update(['updated_at' => date('Y-m-d H:i:s')]);
        }

        return $this->response->setJSON([
            'response'     => $assistantMsg['content'] ?? '',
            'toolCalls'    => $assistantMsg['tool_calls'] ?? null,
            'usage'        => $llmResponse['usage'] ?? null,
            'model'        => $llmModel,
            'finishReason' => $choice['finish_reason'] ?? 'unknown',
        ]);
    }

    /**
     * Execute MCP tool call with user identity
     */
    public function mcpToolCall(): ResponseInterface
    {
        if (! session('username')) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $input    = $this->request->getJSON(true);
        $username = session('username');
        $mcpUrl   = env('MCP_HTTP_URL', 'http://127.0.0.1:8090') . '/mcp';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $mcpUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($input['payload'] ?? []),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                'Accept: application/json, text/event-stream',
                'X-Username: ' . $username,
                ! empty($input['sessionId']) ? 'Mcp-Session-Id: ' . $input['sessionId'] : null,
            ]),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        // Save tool result to DB
        $chatSessionId = $input['chatSessionId'] ?? null;
        if ($chatSessionId && ! empty($input['toolCallId']) && ! empty($input['toolName'])) {
            $this->messageModel->saveToolResult(
                (int) $chatSessionId,
                $input['toolCallId'],
                $input['toolName'],
                $response
            );
        }

        return $this->response->setStatusCode($httpCode)->setJSON($result);
    }

    // ═══════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════

    private function buildSystemPrompt(array $mcpTools, string $username): string
    {
        $now = date('Y-m-d');
        $toolsDesc = '';

        if (! empty($mcpTools)) {
            $toolsDesc = "\n## Available MCP Tools\n";
            foreach ($mcpTools as $tool) {
                $name = $tool['name'] ?? 'unknown';
                $desc = $tool['description'] ?? '';
                $toolsDesc .= "- `$name`: $desc\n";
            }
        }

        return "You are Arteri AI Assistant — a helpful archive management AI for Arteri 2, an Indonesian digital archive management system.
You are helping user: $username
You have access to " . count($mcpTools) . " MCP tools for archive management.
{$toolsDesc}
## Guidelines
- Always respond in the same language the user uses (Bahasa Indonesia or English).
- Use MCP tools whenever relevant — don't just describe what tools exist, actually CALL them.
- For search queries: use `natural_language_search` or `list_accessible_archives`.
- For classification suggestions: use `suggest_classification`.
- For compliance checks: use `check_metadata_completeness`, `generate_compliance_report`.
- For retention: use `get_retention_candidates`, `check_legal_hold`.
- When calling tools, explain what you're doing and present results clearly.
- If a tool returns an error, explain the issue to the user.
- Be concise but thorough. Use tables for structured data.
- Today's date: $now

## Safety Rules
- You CANNOT delete archives or destroy documents — only humans can approve disposition.
- You CANNOT change retention schedules — only suggest them.
- Always warn users about destructive actions before suggesting them.";
    }

    private function loadEnv(): void
    {
        $envFile = ROOTPATH . '.env';
        if (! file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key   = trim($key);
                $value = trim($value);
                if (! getenv($key)) {
                    putenv("$key=$value");
                }
            }
        }
    }
}
