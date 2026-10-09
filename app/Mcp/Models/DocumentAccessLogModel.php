<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class DocumentAccessLogModel extends Model
{
    protected $table            = 'document_access_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'arsip_id', 'accessed_by', 'access_type', 'access_source',
        'details', 'ip_address', 'accessed_at',
    ];
    protected $useTimestamps = false;

    public function log(int $arsipId, string $accessedBy, string $accessType, string $source, ?array $details = null): int
    {
        return (int) $this->insert([
            'arsip_id'      => $arsipId,
            'accessed_by'   => $accessedBy,
            'access_type'   => $accessType,
            'access_source' => $source,
            'details'       => $details ? json_encode($details) : null,
            'ip_address'    => service('request')->getIPAddress() ?? 'cli',
            'accessed_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function getByArsip(int $arsipId, int $limit = 50): array
    {
        return $this->where('arsip_id', $arsipId)
            ->orderBy('accessed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getByUser(string $username, int $limit = 50): array
    {
        return $this->where('accessed_by', $username)
            ->orderBy('accessed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getSuspiciousActivity(int $hours = 24): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        return $this->where('accessed_at >=', $since)
            ->groupBy('accessed_by')
            ->select('accessed_by, COUNT(*) as access_count')
            ->orderBy('access_count', 'DESC')
            ->get()
            ->getResultArray();
    }
}
