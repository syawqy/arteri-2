<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class RetentionActionModel extends Model
{
    protected $table            = 'retention_actions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'arsip_id', 'schedule_id', 'action_type', 'reason', 'status',
        'prepared_by', 'approved_by', 'approved_at',
        'berita_acara_no', 'form_metadata',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat     = 'datetime';

    public function getByArsip(int $arsipId): array
    {
        return $this->where('arsip_id', $arsipId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getDrafts(): array
    {
        return $this->where('status', 'draft')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getPendingApproval(): array
    {
        return $this->where('status', 'pending_approval')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function approve(int $id, string $approvedBy): bool
    {
        return $this->update($id, [
            'status'      => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function reject(int $id, string $approvedBy, string $reason = ''): bool
    {
        return $this->update($id, [
            'status'      => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
            'reason'      => $reason,
        ]);
    }
}
