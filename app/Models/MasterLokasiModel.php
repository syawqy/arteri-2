<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterLokasiModel extends Model
{
    protected $table            = 'master_lokasi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama_lokasi',
    ];
    protected $useTimestamps = false;
}
