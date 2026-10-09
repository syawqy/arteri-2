<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class AiIngestionQueueModel extends Model
{
    protected $table            = 'ai_ingestion_queue';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'filename', 'original_path', 'mime_type', 'file_size',
        'ocr_text', 'raw_metadata', 'status', 'error_message',
        'processed_by', 'uploaded_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat     = 'datetime';

    public function getPending(int $limit = 10): array
    {
        return $this->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getVerificationQueue(int $limit = 20): array
    {
        return $this->where('status', 'queued_verification')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function markProcessing(int $id): bool
    {
        return $this->update($id, ['status' => 'processing']);
    }

    public function markCompleted(int $id, array $data): bool
    {
        return $this->update($id, array_merge($data, ['status' => 'completed']));
    }

    public function markFailed(int $id, string $error): bool
    {
        return $this->update($id, [
            'status'        => 'failed',
            'error_message' => $error,
        ]);
    }

    public function queueForVerification(int $id): bool
    {
        return $this->update($id, ['status' => 'queued_verification']);
    }
}
