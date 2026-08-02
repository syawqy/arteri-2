<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table            = 'chat_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'session_id', 'role', 'content', 'tool_calls',
        'tool_call_id', 'tool_name', 'model', 'usage',
    ];

    protected $useTimestamps = false;

    /**
     * Get all messages for a session, ordered by time
     */
    public function getMessages(int $sessionId): array
    {
        return $this->where('session_id', $sessionId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    /**
     * Save a user message
     */
    public function saveUserMessage(int $sessionId, string $content): int
    {
        return $this->insert([
            'session_id' => $sessionId,
            'role'       => 'user',
            'content'    => $content,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Save an assistant response (text only, no tool calls)
     */
    public function saveAssistantMessage(int $sessionId, string $content, ?string $model = null, ?array $usage = null): int
    {
        return $this->insert([
            'session_id' => $sessionId,
            'role'       => 'assistant',
            'content'    => $content,
            'model'      => $model,
            'usage'      => $usage ? json_encode($usage) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Save an assistant message with tool calls
     */
    public function saveAssistantToolCalls(int $sessionId, ?string $content, array $toolCalls, ?string $model = null): int
    {
        return $this->insert([
            'session_id' => $sessionId,
            'role'       => 'assistant',
            'content'    => $content,
            'tool_calls' => json_encode($toolCalls),
            'model'      => $model,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Save a tool result message
     */
    public function saveToolResult(int $sessionId, string $toolCallId, string $toolName, string $content): int
    {
        return $this->insert([
            'session_id'   => $sessionId,
            'role'         => 'tool',
            'content'      => $content,
            'tool_call_id' => $toolCallId,
            'tool_name'    => $toolName,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete all messages in a session
     */
    public function clearSession(int $sessionId): bool
    {
        return $this->where('session_id', $sessionId)->delete();
    }
}
