<?php

declare(strict_types=1);

namespace App\Mcp\Services;

use App\Models\{UserModel, ArsipModel};

class AccessGuard
{
    private UserModel $userModel;
    private ArsipModel $arsipModel;
    private ?array $currentUser = null;

    public function __construct()
    {
        $this->userModel  = new UserModel();
        $this->arsipModel = new ArsipModel();
    }

    public function authenticate(?string $apiKey = null, ?string $sessionUser = null): ?array
    {
        if ($sessionUser !== null) {
            $user = $this->userModel->where('username', $sessionUser)->first();
            if ($user && $user['tipe'] === 'admin') {
                $this->currentUser = $user;
                return $user;
            }
            return null;
        }

        if ($apiKey !== null) {
            $apiKeyService = new \App\Services\ApiKeyService();
            $keyRecord = $apiKeyService->validate($apiKey);
            if ($keyRecord) {
                $apiKeyService->touchLastUsed($keyRecord['id']);
                $user = $this->userModel->where('username', $keyRecord['created_by'])->first();
                if ($user) {
                    $this->currentUser = $user;
                    return $user;
                }
            }
        }

        // Fallback: auto-login as MCP_TEST_USERNAME for dev/testing
        $testUser = env('MCP_TEST_USERNAME', null);
        if ($testUser !== null) {
            $user = $this->userModel->where('username', $testUser)->first();
            if ($user) {
                $this->currentUser = $user;
                return $user;
            }
        }

        return null;
    }

    public function canAccessKlas(string $kode): bool
    {
        if ($this->currentUser === null) {
            return false;
        }
        if ($this->currentUser['tipe'] === 'admin') {
            return true;
        }
        $aksesKlas = json_decode($this->currentUser['akses_klas'], true) ?? [];
        return in_array($kode, $aksesKlas, true);
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->currentUser === null) {
            return false;
        }
        if ($this->currentUser['tipe'] === 'admin') {
            return true;
        }
        $aksesModul = json_decode($this->currentUser['akses_modul'], true) ?? [];
        return in_array($module, $aksesModul, true);
    }

    public function filterArsip(array $records): array
    {
        return array_values(array_filter($records, function (array $record) {
            return $this->canAccessKlas($record['kode']);
        }));
    }

    public function canAccessArsip(array $arsipRecord): bool
    {
        return $this->canAccessKlas($arsipRecord['kode']);
    }

    public function requireAuth(): void
    {
        if ($this->currentUser === null) {
            $this->authenticate();
        }
        if ($this->currentUser === null) {
            throw new \RuntimeException('Authentication required. Provide a valid API key or session.');
        }
    }

    public function requireModule(string $module): void
    {
        $this->requireAuth();
        if (!$this->canAccessModule($module)) {
            throw new \RuntimeException("Access denied to module: {$module}");
        }
    }

    public function getUser(): ?array
    {
        return $this->currentUser;
    }

    public function getUsername(): string
    {
        return $this->currentUser['username'] ?? 'unknown';
    }

    public function logAccess(?int $arsipId, string $accessType, ?array $details = null): void
    {
        if ($this->currentUser === null) {
            return;
        }
        $accessLogModel = new \App\Mcp\Models\DocumentAccessLogModel();
        $accessLogModel->log(
            $arsipId,
            $this->currentUser['username'],
            $accessType,
            'mcp',
            $details
        );
    }
}
