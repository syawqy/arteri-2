<?php

namespace App\Models;

use CodeIgniter\Model;

class SirkulasiModel extends Model
{
    protected $table            = 'sirkulasi';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $dateFormat       = 'datetime';
    protected $allowedFields    = [
        'noarsip',
        'username_peminjam',
        'keperluan',
        'tgl_pinjam',
        'tgl_haruskembali',
        'tgl_pengembalian',
        'tgl_transaksi',
        'deleted_at',
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
     * Cursor-based pagination for circulation search results.
     * Returns records after the given cursor (id) with optional limit.
     *
     * @param int|null $cursor
     * @param string   $keywords
     * @param int      $limit
     * @return array{records: array, next_cursor: int|null, has_more: bool}
     */
    public function searchWithCursor(?int $cursor = null, string $keywords = '', int $limit = 20): array
    {
        $builder = $this->buildSearchQuery($keywords);

        // Cursor-based: get records with id > cursor
        if ($cursor !== null) {
            $builder->where('s.id >', $cursor);
        }

        // Order by id for consistent cursor behavior
        $builder->orderBy('s.id', 'ASC');
        $builder->limit($limit + 1); // Fetch one extra to check if there's more

        $records = $builder->get()->getResultArray();
        $hasMore = count($records) > $limit;

        if ($hasMore) {
            array_pop($records); // Remove the extra record
        }

        $nextCursor = null;
        if (! empty($records)) {
            $nextCursor = (int) end($records)['id'];
        }

        return [
            'records'     => $records,
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ];
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

        // Exclude soft-deleted circulation rows and rows whose arsip was trashed.
        $builder->where('s.deleted_at', null);
        $builder->where('a.deleted_at', null);

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