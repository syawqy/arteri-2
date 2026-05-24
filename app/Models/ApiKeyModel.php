<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ApiKeyModel extends Model
{
    protected $table            = 'api_keys';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'name',
        'key_prefix',
        'key_hash',
        'created_by',
        'rate_limit',
        'is_active',
        'expires_at',
        'last_used_at',
        'created_at',
        'revoked_at',
    ];
    protected $useTimestamps = false;

    /**
     * Find an active, non-expired API key record by plain-text key.
     */
    public function findActiveByPlainKey(string $plainKey): ?array
    {
        $hash = hash('sha256', $plainKey);

        $record = $this->where('key_hash', $hash)
            ->where('is_active', 1)
            ->where('revoked_at', null)
            ->first();

        if ($record === null) {
            return null;
        }

        if (! empty($record['expires_at']) && strtotime($record['expires_at']) < time()) {
            return null;
        }

        return $record;
    }

    /**
     * List active API keys (without secrets).
     *
     * @return list<array<string, mixed>>
     */
    public function listActive(): array
    {
        return $this->where('is_active', 1)
            ->where('revoked_at', null)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function touchLastUsed(int $id): void
    {
        $this->update($id, ['last_used_at' => date('Y-m-d H:i:s')]);
    }

    public function revokeKey(int $id): bool
    {
        return $this->update($id, [
            'is_active'  => 0,
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
