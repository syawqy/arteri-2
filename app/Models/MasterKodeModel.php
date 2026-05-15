<?php

namespace App\Models;

use App\Traits\MasterCacheTrait;
use CodeIgniter\Model;

class MasterKodeModel extends Model
{
    use MasterCacheTrait;

    protected $table            = 'master_kode';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'kode',
        'nama',
        'retensi',
    ];
    protected $useTimestamps = false;

    /**
     * Cache prefix for this model
     * @var string
     */
    protected string $cachePrefix = 'master_kode_';

    /**
     * Search classification codes by keyword.
     *
     * @param string $keyword
     * @return array
     */
    public function search(string $keyword = ''): array
    {
        $builder = $this->builder();

        if ($keyword !== '') {
            $builder->groupStart()
                ->like('kode', $keyword)
                ->orLike('nama', $keyword)
                ->groupEnd();
        }

        return $builder->orderBy('kode', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all kode for dropdown with caching.
     *
     * @return array
     */
    public function getForDropdown(): array
    {
        $data = $this->getAllCached();
        
        $options = [];
        foreach ($data as $row) {
            $options[$row['id']] = $row['kode'] . ' - ' . $row['nama'];
        }
        
        return $options;
    }
}
