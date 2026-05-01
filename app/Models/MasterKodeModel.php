<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterKodeModel extends Model
{
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
}
