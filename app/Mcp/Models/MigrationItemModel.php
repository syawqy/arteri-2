<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class MigrationItemModel extends Model
{
    protected $table            = 'migration_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'job_id', 'source_path', 'filename', 'file_size', 'mime_type',
        'raw_metadata', 'mapped_metadata', 'status', 'arsip_id', 'error_message',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;
    protected $dateFormat     = 'datetime';

    public function getByJob(int $jobId, ?string $status = null): array
    {
        $builder = $this->where('job_id', $jobId);
        if ($status !== null) {
            $builder->where('status', $status);
        }
        return $builder->orderBy('id', 'ASC')->get()->getResultArray();
    }

    public function getStats(int $jobId): array
    {
        $db = \Config\Database::connect();
        $result = $db->query(
            "SELECT status, COUNT(*) as count FROM migration_items WHERE job_id = ? GROUP BY status",
            [$jobId]
        )->getResultArray();

        $stats = ['total' => 0, 'pending' => 0, 'imported' => 0, 'skipped' => 0, 'failed' => 0];
        foreach ($result as $row) {
            $stats[$row['status']] = (int) $row['count'];
            $stats['total'] += (int) $row['count'];
        }
        return $stats;
    }
}
