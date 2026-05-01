<?php

namespace App\Models;

use CodeIgniter\Model;

class SirkulasiModel extends Model
{
    protected $table            = 'sirkulasi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'noarsip',
        'username_peminjam',
        'keperluan',
        'tgl_pinjam',
        'tgl_haruskembali',
        'tgl_pengembalian',
        'tgl_transaksi',
    ];
    protected $useTimestamps = false;

    /**
     * Search circulation records.
     *
     * @param string $keywords
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function search(string $keywords = '', int $limit = 20, int $offset = 0): array
    {
        $builder = $this->buildSearchQuery($keywords);
        $builder->limit($limit, $offset);

        return $builder->get()->getResultArray();
    }

    /**
     * Return the total row count for the current circulation search.
     *
     * @param string $keywords
     * @return int
     */
    public function searchCount(string $keywords = ''): int
    {
        $builder = $this->buildSearchQuery($keywords);

        return (int) $builder->countAllResults();
    }

    /**
     * Mark an archive as returned by setting the return date to now.
     *
     * @param int|string $id
     * @return bool
     */
    public function returnArchive(int|string $id): bool
    {
        return $this->update($id, [
            'tgl_pengembalian' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Internal helper that constructs the joined builder for circulation search.
     *
     * @param string $keywords
     * @return \CodeIgniter\Database\BaseBuilder
     */
    protected function buildSearchQuery(string $keywords = ''): \CodeIgniter\Database\BaseBuilder
    {
        $builder = $this->db->table('sirkulasi s');
        $builder->select('s.*, u.username, (IF(CURDATE() > s.tgl_haruskembali, \'Terlambat\', \'Dipinjam\')) as status');
        $builder->join('data_arsip a', 'a.noarsip = s.noarsip');
        $builder->join('master_user u', 's.username_peminjam = u.username');

        if ($keywords !== '') {
            $builder->groupStart()
                ->like('s.noarsip', $keywords)
                ->orLike('s.username_peminjam', $keywords)
                ->orLike('s.keperluan', $keywords)
                ->groupEnd();
        }

        return $builder;
    }
}
