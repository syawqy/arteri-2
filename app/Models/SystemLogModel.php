<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemLogModel extends Model
{
    protected $table            = 'system_log';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'kode_transaksi',
        'username_transaksi',
        'tgl_transaksi',
        'aksi',
        'tabel',
        'record_id',
        'detail',
        'ip_address',
    ];
    protected $useTimestamps = false;

    public function log(string $aksi, string $tabel, ?int $recordId = null, ?array $detail = null): ?int
    {
        return $this->insert([
            'kode_transaksi'     => $aksi,
            'username_transaksi' => session('username') ?? 'system',
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => $aksi,
            'tabel'              => $tabel,
            'record_id'          => $recordId,
            'detail'             => $detail ? json_encode($detail) : null,
            'ip_address'         => service('request')->getIPAddress(),
        ], false);
    }

    public function search(array $filters = [], int $perPage = 20): array
    {
        $builder = $this->orderBy('tgl_transaksi', 'DESC')->orderBy('id', 'DESC');

        if (! empty($filters['aksi'])) {
            $builder->where('aksi', $filters['aksi']);
        }
        if (! empty($filters['tabel'])) {
            $builder->where('tabel', $filters['tabel']);
        }
        if (! empty($filters['username'])) {
            $builder->like('username_transaksi', $filters['username']);
        }
        if (! empty($filters['tanggal_dari'])) {
            $builder->where('tgl_transaksi >=', $filters['tanggal_dari'] . ' 00:00:00');
        }
        if (! empty($filters['tanggal_sampai'])) {
            $builder->where('tgl_transaksi <=', $filters['tanggal_sampai'] . ' 23:59:59');
        }

        return $builder->paginate($perPage);
    }
}
