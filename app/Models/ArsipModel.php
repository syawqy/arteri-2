<?php

namespace App\Models;

use CodeIgniter\Model;

class ArsipModel extends Model
{
    protected $table            = 'data_arsip';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $deletedField     = 'deleted_at';
    protected $allowedFields    = [
        'noarsip',
        'pencipta',
        'unit_pengolah',
        'tanggal',
        'uraian',
        'ket',
        'kode',
        'jumlah',
        'nobox',
        'lokasi',
        'media',
        'file',
        'username',
        'deleted_at',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'tgl_input';
    protected $updatedField  = 'tgl_update';
    protected $dateFormat    = 'datetime';

    /**
     * Build the complex search query with Query Builder.
     *
     * @param string $keywords Simple search keyword
     * @param array  $filters  Advanced filters:
     *                         noarsip, tanggal, uraian, ket, kode, retensi,
     *                         penc, peng, lok, med, nobox
     * @param int    $limit
     * @param int    $offset
     * @return array
     */
    public function search(string $keywords = '', array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $builder = $this->buildSearchQuery($keywords, $filters);
        $builder->limit($limit, $offset);

        return $builder->get()->getResultArray();
    }

    /**
     * Return the total row count for the current search.
     *
     * @param string $keywords
     * @param array  $filters
     * @return int
     */
    public function searchCount(string $keywords = '', array $filters = []): int
    {
        $builder = $this->buildSearchQuery($keywords, $filters);

        return (int) $builder->countAllResults();
    }

    /**
     * Cursor-based pagination for search results.
     * Returns records after the given cursor (id) with optional limit.
     *
     * @param int|null $cursor    Last seen ID (exclusive - get records after this)
     * @param string   $keywords
     * @param array    $filters
     * @param int      $limit
     * @return array{records: array, next_cursor: int|null, has_more: bool}
     */
    public function searchWithCursor(?int $cursor = null, string $keywords = '', array $filters = [], int $limit = 20): array
    {
        $builder = $this->buildSearchQuery($keywords, $filters);

        // Cursor-based: get records with id > cursor
        if ($cursor !== null) {
            $builder->where('a.id >', $cursor);
        }

        // Order by id for consistent cursor behavior
        $builder->orderBy('a.id', 'ASC');
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
     * Get a single archive record with all master table joins.
     *
     * @param int|string $id
     * @return array|null
     */
    public function getDetail(int|string $id): ?array
    {
        return $this->db->table('data_arsip a')
            ->select('a.*, p.nama_pencipta, p2.nama_pengolah, k.nama, k.kode as nama_kode, l.nama_lokasi, m.nama_media, DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR) as b, (IF(DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR) < CURDATE(), \'sudah\', \'belum\')) as f')
            ->join('master_pencipta p', 'p.id = a.pencipta', 'left')
            ->join('master_pengolah p2', 'p2.id = a.unit_pengolah', 'left')
            ->join('master_kode k', 'k.id = a.kode', 'left')
            ->join('master_lokasi l', 'l.id = a.lokasi', 'left')
            ->join('master_media m', 'm.id = a.media', 'left')
            ->where('a.id', $id)
            ->where('a.deleted_at', null)
            ->get()
            ->getRowArray();
    }

    /**
     * Internal helper that constructs the joined builder and applies
     * both simple-keyword and advanced-filter predicates.
     *
     * @param string $keywords
     * @param array  $filters
     * @return \CodeIgniter\Database\BaseBuilder
     */
    protected function buildSearchQuery(string $keywords = '', array $filters = []): \CodeIgniter\Database\BaseBuilder
    {
        $builder = $this->db->table('data_arsip a');
        $builder->select('a.*, k.retensi, DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR) as b, k.kode as nama_kode, (IF(DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR) < CURDATE(), \'sudah\', \'belum\')) as f, nama_lokasi, nama_media, nama_pencipta, nama_pengolah');
        $builder->join('master_kode k', 'k.id = a.kode');
        $builder->join('master_lokasi l', 'l.id = a.lokasi');
        $builder->join('master_media m', 'm.id = a.media');
        $builder->join('master_pencipta p', 'p.id = a.pencipta');
        $builder->join('master_pengolah pn', 'pn.id = a.unit_pengolah');

        // Exclude soft-deleted records (raw builder bypasses model scoping).
        $builder->where('a.deleted_at', null);

        if ($keywords !== '') {
            // Simple search: OR conditions
            $builder->groupStart()
                ->like('a.noarsip', $keywords)
                ->orLike('a.uraian', $keywords)
                ->orLike('a.nobox', $keywords)
                ->groupEnd();
        } else {
            // Advanced search: AND conditions
            if (!empty($filters['noarsip'])) {
                $builder->like('a.noarsip', $filters['noarsip']);
            }
            if (!empty($filters['tanggal'])) {
                $builder->like('a.tanggal', $filters['tanggal']);
            }
            if (!empty($filters['uraian'])) {
                $builder->like('a.uraian', $filters['uraian']);
            }
            if (!empty($filters['ket']) && $filters['ket'] !== 'all') {
                $builder->where('a.ket', $filters['ket']);
            }
            if (!empty($filters['nobox'])) {
                $builder->like('a.nobox', $filters['nobox']);
            }
            if (!empty($filters['kode']) && $filters['kode'] !== 'all') {
                $builder->like('k.kode', $filters['kode'], 'after');
            }
            if (!empty($filters['retensi']) && $filters['retensi'] !== 'all') {
                if ($filters['retensi'] === 'sudah') {
                    $builder->where('DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR) < CURDATE()', null, false);
                } else {
                    $builder->where('DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR) > CURDATE()', null, false);
                }
            }
            if (!empty($filters['penc']) && $filters['penc'] !== 'all') {
                $builder->where('a.pencipta', $filters['penc']);
            }
            if (!empty($filters['peng']) && $filters['peng'] !== 'all') {
                $builder->where('a.unit_pengolah', $filters['peng']);
            }
            if (!empty($filters['lok']) && $filters['lok'] !== 'all') {
                $builder->where('a.lokasi', $filters['lok']);
            }
            if (!empty($filters['med']) && $filters['med'] !== 'all') {
                $builder->where('a.media', $filters['med']);
            }
        }

        // Session-based klasifikasi access filter
        $aksesKlas = session('akses_klas');
        if (!empty($aksesKlas)) {
            $prefixes = array_values(array_filter(array_map('trim', explode(',', $aksesKlas))));
            sort($prefixes);

            if ($prefixes !== []) {
                $builder->groupStart();
                foreach ($prefixes as $index => $prefix) {
                    if ($index === 0) {
                        $builder->like('k.kode', $prefix, 'after');
                    } else {
                        $builder->orLike('k.kode', $prefix, 'after');
                    }
                }
                $builder->groupEnd();
            }
        }

        return $builder;
    }
}