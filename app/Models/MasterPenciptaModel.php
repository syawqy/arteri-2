<?php

namespace App\Models;

use App\Traits\MasterCacheTrait;
use CodeIgniter\Model;

class MasterPenciptaModel extends Model
{
    use MasterCacheTrait;

    protected $table            = 'master_pencipta';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $allowedFields    = [
        'nama_pencipta',
        'deleted_at',
    ];
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    /**
     * Cache prefix for this model
     * @var string
     */
    protected string $cachePrefix = 'master_pencipta_';

    /**
     * Get all pencipta for dropdown with caching.
     *
     * @return array
     */
    public function getForDropdown(): array
    {
        $data = $this->getAllCached();
        
        $options = [];
        foreach ($data as $row) {
            $options[$row['id']] = $row['nama_pencipta'];
        }
        
        return $options;
    }
}
