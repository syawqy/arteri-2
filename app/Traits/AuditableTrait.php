<?php

namespace App\Traits;

use App\Models\SystemLogModel;

trait AuditableTrait
{
    protected function logActivity(string $aksi, string $tabel, ?int $recordId = null, ?array $detail = null): void
    {
        (new SystemLogModel())->log($aksi, $tabel, $recordId, $detail);
    }

    protected function logCrud(string $action, string $table, int $id, ?array $old = null, ?array $new = null): void
    {
        $detail = [];
        if ($old !== null) {
            $detail['old'] = $old;
        }
        if ($new !== null) {
            $detail['new'] = $new;
        }
        $this->logActivity($action, $table, $id, $detail ?: null);
    }

    protected function logLogin(string $username, bool $success, ?string $failReason = null): void
    {
        $aksi  = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        $detail = null;
        if (! $success && $failReason) {
            $detail = ['reason' => $failReason];
        }
        $this->logActivity($aksi, 'master_user', null, $detail);
    }
}
