<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ChatSessionModel extends Model
{
    protected $table            = 'chat_sessions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = ['user_id', 'title'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get all sessions for a user, with last message preview
     */
    public function getSessionsForUser(string $userId): array
    {
        $sessions = $this->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $db = $this->db;

        foreach ($sessions as &$session) {
            $lastMsg = $db->table('chat_messages')
                ->select('content, role, tool_name')
                ->where('session_id', $session['id'])
                ->orderBy('created_at', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            $session['preview'] = '';
            if ($lastMsg) {
                if ($lastMsg['role'] === 'assistant' && $lastMsg['content']) {
                    $session['preview'] = mb_substr(strip_tags($lastMsg['content']), 0, 80);
                } elseif ($lastMsg['role'] === 'user' && $lastMsg['content']) {
                    $session['preview'] = mb_substr($lastMsg['content'], 0, 80);
                } elseif ($lastMsg['role'] === 'tool' && $lastMsg['tool_name']) {
                    $session['preview'] = '🔧 ' . $lastMsg['tool_name'];
                }
            }

            $session['message_count'] = $db->table('chat_messages')
                ->where('session_id', $session['id'])
                ->countAllResults();
        }

        return $sessions;
    }

    /**
     * Create a new session
     */
    public function createSession(string $userId, string $title = 'New Chat'): int
    {
        $now = date('Y-m-d H:i:s');
        return $this->insert([
            'user_id'    => $userId,
            'title'      => $title,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Update session title
     */
    public function updateTitle(int $sessionId, string $title): bool
    {
        return $this->update($sessionId, ['title' => $title]);
    }

    /**
     * Delete a session (messages cascade)
     */
    public function deleteSession(int $sessionId, string $userId): bool
    {
        return $this->where('id', $sessionId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Verify session belongs to user
     */
    public function userOwns(int $sessionId, string $userId): bool
    {
        return $this->where('id', $sessionId)
            ->where('user_id', $userId)
            ->countAllResults() > 0;
    }
}
