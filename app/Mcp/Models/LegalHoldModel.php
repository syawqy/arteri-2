<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class LegalHoldModel extends Model
{
    protected $table            = 'legal_holds';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'arsip_id', 'reason', 'case_ref', 'imposed_by',
        'imposed_at', 'released_by', 'released_at', 'is_active',
    ];
    protected $useTimestamps = false;

    public function getActiveForArsip(int $arsipId): ?array
    {
        $result = $this->where('arsip_id', $arsipId)
            ->where('is_active', 1)
            ->first();
        return $result ?: null;
    }

    public function getAllActive(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('imposed_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function impose(int $arsipId, string $reason, string $imposedBy, ?string $caseRef = null): int
    {
        return (int) $this->insert([
            'arsip_id'    => $arsipId,
            'reason'      => $reason,
            'case_ref'    => $caseRef,
            'imposed_by'  => $imposedBy,
            'imposed_at'  => date('Y-m-d H:i:s'),
            'is_active'   => 1,
        ]);
    }

    public function release(int $id, string $releasedBy): bool
    {
        return $this->update($id, [
            'is_active'   => 0,
            'released_by' => $releasedBy,
            'released_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
