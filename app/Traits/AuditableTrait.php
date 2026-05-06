<?php

namespace App\Traits;

use App\Models\SystemLogModel;

trait AuditableTrait
{
    protected function logActivity(string $aksi, string $tabel, ?int $recordId = null, ?array $detail = null): void
    {
        $log = new SystemLogModel();

        $log->insert([
            'kode_transaksi'     => $aksi,
            'username_transaksi' => session('username') ?? 'system',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => $aksi,
            'tabel'              => $tabel,
            'record_id'          => $recordId,
            'detail'             => $detail ? json_encode($detail) : null,
            'ip_address'         => $this->request ? $this->request->getIPAddress() : null,
        ]);
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

    protected function logPageView(string $page): void
    {
        $this->logActivity('PAGE_VIEW', $page);
    }

    protected function logAction(string $action, string $table, ?int $recordId = null, ?array $detail = null): void
    {
        $this->logActivity($action, $table, $recordId, $detail);
    }
}
