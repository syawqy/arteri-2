<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiKeyModel;

class ApiKeyService
{
    private ApiKeyModel $apiKeyModel;

    public function __construct(?ApiKeyModel $apiKeyModel = null)
    {
        $this->apiKeyModel = $apiKeyModel ?? new ApiKeyModel();
    }

    /**
     * Generate a new API key. Plain key is returned once; only hash is stored.
     *
     * @return array{record: array<string, mixed>, plain_key: string}
     */
    public function generate(
        string $name,
        string $createdBy,
        int $rateLimit = 60,
        ?string $expiresAt = null
    ): array {
        $plainKey = 'art_' . bin2hex(random_bytes(24));
        $prefix   = substr($plainKey, 0, 8);

        $record = [
            'name'        => $name,
            'key_prefix'  => $prefix,
            'key_hash'    => hash('sha256', $plainKey),
            'created_by'  => $createdBy,
            'rate_limit'  => max(1, $rateLimit),
            'is_active'   => 1,
            'expires_at'  => $expiresAt,
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $id = $this->apiKeyModel->insert($record);
        $record['id'] = $id;

        return [
            'record'    => $record,
            'plain_key' => $plainKey,
        ];
    }

    public function validate(string $plainKey): ?array
    {
        return $this->apiKeyModel->findActiveByPlainKey($plainKey);
    }

    public function revoke(int $id): bool
    {
        return $this->apiKeyModel->revokeKey($id);
    }

    public function touchLastUsed(int $id): void
    {
        $this->apiKeyModel->touchLastUsed($id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listKeys(): array
    {
        $keys = $this->apiKeyModel->listActive();

        return array_map(static function (array $key): array {
            unset($key['key_hash']);

            return $key;
        }, $keys);
    }
}
