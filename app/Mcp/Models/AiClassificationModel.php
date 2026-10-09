<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class AiClassificationModel extends Model
{
    protected $table            = 'ai_classifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'arsip_id', 'suggested_kode', 'confidence', 'reasoning',
        'matched_rules', 'suggested_series', 'status',
        'approved_by', 'approved_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat     = 'datetime';

    public function getSuggestionsForArsip(int $arsipId): array
    {
        return $this->where('arsip_id', $arsipId)
            ->orderBy('confidence', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getPendingSuggestions(int $limit = 20): array
    {
        return $this->where('status', 'suggested')
            ->orderBy('confidence', 'DESC')
            ->limit($limit)
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
}
