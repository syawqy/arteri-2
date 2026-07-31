<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class AiVerificationQueueModel extends Model
{
    protected $table            = 'ai_verification_queue';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'ingestion_id', 'field_name', 'ai_value', 'ai_confidence',
        'human_value', 'status', 'verified_by', 'verified_at', 'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat     = 'datetime';

    public function getPendingForIngestion(int $ingestionId): array
    {
        return $this->where('ingestion_id', $ingestionId)
            ->where('status', 'pending')
            ->get()
            ->getResultArray();
    }

    public function getAllPending(int $limit = 50): array
    {
        return $this->where('status', 'pending')
            ->orderBy('ai_confidence', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function approve(int $id, string $verifiedBy, ?string $humanValue = null): bool
    {
        $data = [
            'status'       => 'approved',
            'verified_by'  => $verifiedBy,
            'verified_at'  => date('Y-m-d H:i:s'),
        ];
        if ($humanValue !== null) {
            $data['human_value'] = $humanValue;
        }
        return $this->update($id, $data);
    }

    public function reject(int $id, string $verifiedBy, string $notes = ''): bool
    {
        return $this->update($id, [
            'status'      => 'rejected',
            'verified_by' => $verifiedBy,
            'verified_at' => date('Y-m-d H:i:s'),
            'notes'       => $notes,
        ]);
    }
}
