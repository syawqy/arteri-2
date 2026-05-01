<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterPenciptaModel extends Model
{
    protected $table            = 'master_pencipta';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama_pencipta',
    ];
    protected $useTimestamps = false;
}
