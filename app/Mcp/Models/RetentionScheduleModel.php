<?php

declare(strict_types=1);

namespace App\Mcp\Models;

use CodeIgniter\Model;

class RetentionScheduleModel extends Model
{
    protected $table            = 'retention_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'kode_klas', 'nama_jadwal', 'retensi_aktif', 'retensi_inaktif',
        'jenis_disposisi', 'dasar_hukum', 'keterangan', 'is_active',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat     = 'datetime';

    public function getByKode(string $kode): ?array
    {
        $result = $this->where('kode_klas', $kode)
            ->where('is_active', 1)
            ->first();
        return $result ?: null;
    }

    public function getActive(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('kode_klas', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getTotalRetention(array $schedule): int
    {
        return (int) $schedule['retensi_aktif'] + (int) $schedule['retensi_inaktif'];
    }

    public function calculateRetentionEnd(array $schedule, string $arsipDate): ?string
    {
        $date  = new \DateTime($arsipDate);
        $years = $this->getTotalRetention($schedule);
        $date->modify("+{$years} years");
        return $date->format('Y-m-d');
    }
}
