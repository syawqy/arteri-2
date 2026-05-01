<?php

namespace App\Models;

use CodeIgniter\Model;

class MasterMediaModel extends Model
{
    protected $table            = 'master_media';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'nama_media',
    ];
    protected $useTimestamps = false;
}
