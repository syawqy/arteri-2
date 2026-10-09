<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class MigrationJobModel extends Model
{
    protected $table            = 'migration_jobs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'source_type', 'source_config', 'status', 'total_items',
        'processed_items', 'error_items', 'field_mapping',
        'error_log', 'started_by',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat     = 'datetime';

    public function createJob(string $sourceType, array $config, string $startedBy): int
    {
        return (int) $this->insert([
            'source_type'   => $sourceType,
            'source_config' => json_encode($config),
            'status'        => 'created',
            'started_by'    => $startedBy,
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $data = ['status' => $status];
        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        return $this->update($id, $data);
    }

    public function getActive(): array
    {
        return $this->whereNotIn('status', ['completed', 'failed'])
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}
