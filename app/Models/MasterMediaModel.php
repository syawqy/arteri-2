<?php

namespace App\Models;

use App\Traits\MasterCacheTrait;
use CodeIgniter\Model;

class MasterMediaModel extends Model
{
    use MasterCacheTrait;

    protected $table            = 'master_media';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $allowedFields    = [
        'nama_media',
        'deleted_at',
    ];
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    /**
     * Cache prefix for this model
     * @var string
     */
    protected string $cachePrefix = 'master_media_';

    /**
     * Get all media for dropdown with caching.
     *
     * @return array
     */
    public function getForDropdown(): array
    {
        $data = $this->getAllCached();
        
        $options = [];
        foreach ($data as $row) {
            $options[$row['id']] = $row['nama_media'];
        }
        
        return $options;
    }
}
