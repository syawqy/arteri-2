<?php
/**
 * Arteri MCP Chat Backend API
 * 
 * Proxies LLM requests with MCP tools schema.
 * POST /mcp/chat-api.php
 * Body: { message: string, history?: array, mcpTools?: array }
 * Returns: { response: string, toolCalls?: array, usage?: object }
 * 
 * LLM config via env vars or fallback defaults:
 *   LLM_BASE_URL, LLM_API_KEY, LLM_MODEL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Load .env from project root
$envFile = dirname(__DIR__, 2) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Also check hermes env for API key
$hermesEnv = getenv('HOME') . '/.hermes/.env';
if (file_exists($hermesEnv)) {
    foreach (file($hermesEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

// LLM Configuration
$llmBaseUrl  = getenv('LLM_BASE_URL') ?: 'https://ai.sumopod.com/v1';
$llmApiKey   = getenv('LLM_API_KEY') ?: getenv('XIAOMI_API_KEY') ?: '';
$llmModel    = getenv('LLM_MODEL') ?: 'gpt-4.1-nano';

// Parse request body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || (empty($input['messages']) && empty($input['message']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: messages or message']);
    exit;
}

// Support both formats: new (messages array) and legacy (message + history)
if (!empty($input['messages'])) {
    // New format: full message array from frontend (includes user, assistant, tool roles)
    $chatMessages = $input['messages'];
} else {
    // Legacy format: message + history
    $chatMessages = array_merge(
        $input['history'] ?? [],
        [['role' => 'user', 'content' => $input['message']]]
    );
}
$mcpTools = $input['mcpTools'] ?? [];

// Build system prompt
$now = date('Y-m-d');
$toolsDesc = '';
if (!empty($mcpTools)) {
    $toolsDesc = "\n## Available MCP Tools\n";
    foreach ($mcpTools as $tool) {
        $name = $tool['name'] ?? 'unknown';
        $desc = $tool['description'] ?? '';
        $toolsDesc .= "- `$name`: $desc\n";
    }
}

$systemPrompt = "You are Arteri AI Assistant — a helpful archive management AI for Arteri 2, an Indonesian digital archive management system.
You have access to " . count($mcpTools) . " MCP tools for archive management.
{$toolsDesc}
## Guidelines
- Always respond in the same language the user uses (Bahasa Indonesia or English).
- Use MCP tools whenever relevant — don't just describe what tools exist, actually CALL them.
- When calling tools, explain what you're doing and present results clearly.
- Be concise but thorough. Use tables for structured data.
- Today's date: {$now}

## Safety Rules
- You CANNOT delete archives or destroy documents — only humans can approve disposition.
- You CANNOT change retention schedules — only suggest them.
- Always warn users about destructive actions before suggesting them.";

// Build messages array
$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
];

// Add all chat messages (preserve tool_calls and tool_call_id for OpenAI API)
foreach ($chatMessages as $msg) {
    $entry = [
        'role' => $msg['role'] ?? 'user',
        'content' => $msg['content'] ?? '',
    ];
    // Forward tool_calls from assistant messages
    if (!empty($msg['tool_calls'])) {
        $entry['tool_calls'] = $msg['tool_calls'];
    }
    // Forward tool_call_id for tool result messages
    if (!empty($msg['tool_call_id'])) {
        $entry['tool_call_id'] = $msg['tool_call_id'];
    }
    $messages[] = $entry;
}

// Convert MCP tools to OpenAI function calling format
$tools = [];
foreach ($mcpTools as $tool) {
    $schema = $tool['inputSchema'] ?? ['type' => 'object', 'properties' => []];
    // Ensure 'properties' is an object, not an array (PHP encodes empty [] as [], LLM expects {})
    if (isset($schema['properties']) && empty($schema['properties'])) {
        $schema['properties'] = (object)[];
    }
    $tools[] = [
        'type' => 'function',
        'function' => [
            'name' => $tool['name'],
            'description' => $tool['description'] ?? '',
            'parameters' => $schema,
        ],
    ];
}

// Build LLM request
$llmPayload = [
    'model' => $llmModel,
    'messages' => $messages,
    'max_tokens' => $input['maxTokens'] ?? 4096,
    'temperature' => $input['temperature'] ?? 0.3,
];

if (!empty($tools)) {
    $llmPayload['tools'] = $tools;
}

// Call LLM API
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $llmBaseUrl . '/chat/completions',
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($llmPayload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $llmApiKey,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['error' => 'LLM API connection error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);
    $errorData = json_decode($response, true);
    echo json_encode([
        'error' => 'LLM API error (HTTP ' . $httpCode . '): ' . ($errorData['error']['message'] ?? $response),
        'baseUrl' => $llmBaseUrl,
        'model' => $llmModel,
    ]);
    exit;
}

$llmResponse = json_decode($response, true);

if (!$llmResponse || empty($llmResponse['choices'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid LLM response', 'raw' => $response]);
    exit;
}

$choice = $llmResponse['choices'][0];
$assistantMsg = $choice['message'];

// Build response
$result = [
    'response' => $assistantMsg['content'] ?? '',
    'toolCalls' => $assistantMsg['tool_calls'] ?? null,
    'usage' => $llmResponse['usage'] ?? null,
    'model' => $llmModel,
    'finishReason' => $choice['finish_reason'] ?? 'unknown',
];

echo json_encode($result);
