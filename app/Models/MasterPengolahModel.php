<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterPengolahModel extends Model
{
    protected $table            = 'master_pengolah';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama_pengolah',
    ];
    protected $useTimestamps = false;
}
