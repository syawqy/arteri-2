<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DashboardModel - Analytics queries untuk dashboard
 * 
 * @method int countTotalArsip()
 * @method int countSedangDipinjam()
 * @method int countArsipOverdue()
 * @method array getStatistikPerKlasifikasi()
 * @method array getStatistikAktivitasBulanan(int $months = 6)
 */
class DashboardModel extends Model
{
    protected $table            = 'data_arsip';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $allowedFields    = [];

    /**
     * Count total arsip dalam sistem
     */
    public function countTotalArsip(): int
    {
        return (int) $this->countAllResults();
    }

    /**
     * Count arsip yang sedang dipinjam (belum dikembalikan)
     */
    public function countSedangDipinjam(): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table('sirkulasi s');
        $builder->select('COUNT(DISTINCT s.noarsip) as total');
        $builder->where('s.tgl_pengembalian IS NULL');
        $builder->where('s.deleted_at', null);

        $result = $builder->get()->getRowArray();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Count arsip overdue (lewat tanggal harus kembali dan belum dikembalikan)
     */
    public function countArsipOverdue(): int
    {
        $db = \Config\Database::connect();
        $builder = $db->table('sirkulasi s');
        $builder->select('COUNT(DISTINCT s.noarsip) as total');
        $builder->where('s.tgl_pengembalian IS NULL');
        $builder->where('s.tgl_haruskembali <', date('Y-m-d H:i:s'));
        $builder->where('s.deleted_at', null);
        
        $result = $builder->get()->getRowArray();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get statistik arsip per klasifikasi kode
     */
    public function getStatistikPerKlasifikasi(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('data_arsip a');
        $builder->select('k.kode, k.nama, COUNT(a.id) as total');
        $builder->join('master_kode k', 'k.id = a.kode', 'left');
        $builder->where('a.deleted_at', null);
        $builder->groupBy('a.kode');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get statistik aktivitas bulanan (peminjaman per bulan)
     * 
     * @param int $months Jumlah bulan ke belakang
     */
    public function getStatistikAktivitasBulanan(int $months = 6): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('sirkulasi s');
        $builder->select('DATE_FORMAT(s.tgl_pinjam, "%Y-%m") as bulan, COUNT(*) as total');
        $builder->where('s.tgl_pinjam >=', date('Y-m-d', strtotime("-{$months} months")));
        $builder->where('s.deleted_at', null);
        $builder->groupBy('DATE_FORMAT(s.tgl_pinjam, "%Y-%m")');
        $builder->orderBy('bulan', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get statistik berdasarkan lokasi
     */
    public function getStatistikPerLokasi(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('data_arsip a');
        $builder->select('l.nama_lokasi, COUNT(a.id) as total');
        $builder->join('master_lokasi l', 'l.id = a.lokasi', 'left');
        $builder->where('a.deleted_at', null);
        $builder->groupBy('a.lokasi');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get statistik berdasarkan media
     */
    public function getStatistikPerMedia(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('data_arsip a');
        $builder->select('m.nama_media, COUNT(a.id) as total');
        $builder->join('master_media m', 'm.id = a.media', 'left');
        $builder->where('a.deleted_at', null);
        $builder->groupBy('a.media');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get statistik berdasarkan pencipta
     */
    public function getStatistikPerPencipta(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('data_arsip a');
        $builder->select('p.nama_pencipta, COUNT(a.id) as total');
        $builder->join('master_pencipta p', 'p.id = a.pencipta', 'left');
        $builder->where('a.deleted_at', null);
        $builder->groupBy('a.pencipta');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get statistik berdasarkan status keterangan (asli/copy)
     */
    public function getStatistikPerKet(): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('data_arsip a');
        $builder->select('a.ket, COUNT(a.id) as total');
        $builder->where('a.deleted_at', null);
        $builder->groupBy('a.ket');
        $builder->orderBy('total', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get semua statistik dashboard dalam satu method
     */
    public function getAllStats(): array
    {
        return [
            'total_arsip' => $this->countTotalArsip(),
            'sedang_dipinjam' => $this->countSedangDipinjam(),
            'overdue' => $this->countArsipOverdue(),
            'per_klasifikasi' => $this->getStatistikPerKlasifikasi(),
            'per_lokasi' => $this->getStatistikPerLokasi(),
            'per_media' => $this->getStatistikPerMedia(),
            'per_pencipta' => $this->getStatistikPerPencipta(),
            'per_ket' => $this->getStatistikPerKet(),
            'aktivitas_bulanan' => $this->getStatistikAktivitasBulanan(6),
        ];
    }
}