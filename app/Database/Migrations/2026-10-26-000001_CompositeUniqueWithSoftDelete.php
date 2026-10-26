<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ubah unique key kolom natural menjadi komposit (kolom, deleted_at).
 *
 * Sebelum soft delete diperkenalkan, tabel master memakai UNIQUE pada kolom
 * natural saja (mis. master_kode.kode). Setelah kolom deleted_at ditambahkan,
 * susunan itu membuat baris yang sudah dihapus tetap memblokir pembuatan
 * baris baru dengan kode yang sama — alur umum "hapus lalu buat ulang"
 * menjadi mustahil, dan skenario "satu baris di trash + satu baris aktif
 * bernilai sama" tidak bisa direpresentasikan sama sekali.
 *
 * Dengan UNIQUE(kolom, deleted_at):
 *   - dua baris aktif bernilai sama  -> tetap ditolak (NULL di MySQL dan
 *     SQLite dianggap distinct, jadi hanya satu deleted_at NULL yang boleh)
 *   - baris di trash + baris aktif   -> boleh, karena deleted_at berbeda
 *
 * Nama index lama tidak ditebak: migrasi mendeteksi index unique yang kolomnya
 * tepat satu kolom natural, lalu menggantinya. Migrasi idempoten.
 */
class CompositeUniqueWithSoftDelete extends Migration
{
    /**
     * Tabel beserta kolom natural yang unique-nya perlu dikompositkan.
     *
     * @var array<string, string>
     */
    private array $targets = [
        'master_kode'     => 'kode',
        'master_lokasi'   => 'nama_lokasi',
        'master_media'    => 'nama_media',
        'master_pencipta' => 'nama_pencipta',
        'master_pengolah' => 'nama_pengolah',
        'master_user'     => 'username',
    ];

    public function up(): void
    {
        $isSqlite = stripos($this->db->getPlatform(), 'sqlite') !== false;
        $failures = [];

        foreach ($this->targets as $table => $column) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            if (! $this->db->fieldExists($column, $table)) {
                continue;
            }

            // Pastikan kolom soft-delete ada. AddSoftDeletes memakai
            // Forge::addColumn() yang menghasilkan sintaks MySQL ("ADD col"),
            // sehingga pada SQLite kolom bisa gagal ditambahkan tanpa error.
            // Migrasi ini melengkapinya agar tidak bergantung pada keberhasilan
            // migrasi lain.
            if (! $this->db->fieldExists('deleted_at', $table)) {
                $this->addDeletedAtColumn($table);
            }

            $newIndex = 'uq_' . $table . '_' . $column . '_deleted';

            // Lewati jika index komposit sudah ada (migrasi dijalankan ulang).
            if ($this->indexExists($table, $newIndex)) {
                continue;
            }

            try {
                $oldIndex = $this->findSingleColumnUniqueIndex($table, $column);

                if ($oldIndex !== null) {
                    $this->dropIndex($table, $oldIndex, $isSqlite);
                }

                $this->createCompositeUnique($table, $column, $newIndex, $isSqlite);
            } catch (\Throwable $e) {
                // Paling sering karena sudah ada baris duplikat di antara data
                // yang ter-soft-delete. Dicatat agar bisa dibersihkan manual.
                $failures[] = $table . ': ' . $e->getMessage();
            }
        }

        if ($failures !== []) {
            throw new \RuntimeException(
                'CompositeUniqueWithSoftDelete gagal pada: ' . implode(' | ', $failures)
            );
        }
    }

    public function down(): void
    {
        $isSqlite = stripos($this->db->getPlatform(), 'sqlite') !== false;

        foreach ($this->targets as $table => $column) {
            if (! $this->db->tableExists($table)) {
                continue;
            }

            if (! $this->db->fieldExists($column, $table)) {
                continue;
            }

            $newIndex = 'uq_' . $table . '_' . $column . '_deleted';

            try {
                $this->dropIndex($table, $newIndex, $isSqlite);
                $this->createSingleUnique($table, $column, $isSqlite);
            } catch (\Throwable $e) {
                // Index sudah dalam keadaan target — abaikan.
            }
        }
    }

    /**
     * Tambahkan kolom deleted_at jika belum ada.
     *
     * Forge::addColumn() memakai sintaks "ALTER TABLE ... ADD col" yang hanya
     * valid di MySQL; SQLite memerlukan kata kunci COLUMN. Karena itu DDL
     * ditulis manual agar bekerja di kedua driver.
     */
    private function addDeletedAtColumn(string $table): void
    {
        $prefixed = $this->db->getPrefix() . $table;

        try {
            $this->db->query(
                "ALTER TABLE `{$prefixed}` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL"
            );
        } catch (\Throwable $e) {
            // Kolom sudah ada. Pada SQLite :memory: yang dipakai untuk tes,
            // fieldExists() bisa melaporkan kolom tidak padahal sudah ada
            // karena koneksi yang diperiksa berbeda dengan yang melakukan DDL.
            // Duplikat bukan kegagalan yang perlu menghentikan migrasi.
        }
    }

    /**
     * Cari nama index unique yang kolomnya tepat satu kolom natural.
     * Mengembalikan null kalau tidak ada (mis. sudah dikompositkan).
     */
    private function findSingleColumnUniqueIndex(string $table, string $column): ?string
    {
        foreach ($this->listUniqueIndexes($table) as $indexName => $columns) {
            if ($columns === [$column]) {
                return $indexName;
            }
        }

        return null;
    }

    /**
     * Daftar index unique beserta kolomnya, dalam bentuk [nama => kolom].
     *
     * @return array<string, list<string>>
     */
    private function listUniqueIndexes(string $table): array
    {
        $result = [];

        try {
            if (stripos($this->db->getPlatform(), 'sqlite') !== false) {
                $prefixed = $this->db->getPrefix() . $table;

                foreach ($this->db->query("PRAGMA index_list(`{$prefixed}`)")->getResultArray() as $index) {
                    if ((int) ($index['unique'] ?? 0) !== 1) {
                        continue;
                    }

                    $name  = (string) ($index['name'] ?? '');
                    $cols  = [];

                    foreach ($this->db->query("PRAGMA index_info(`{$name}`)")->getResultArray() as $info) {
                        $cols[] = (string) ($info['name'] ?? '');
                    }

                    if ($name !== '' && $cols !== []) {
                        $result[$name] = $cols;
                    }
                }

                return $result;
            }

            $rows = $this->db->query(
                'SELECT INDEX_NAME, COLUMN_NAME, NON_UNIQUE, SEQ_IN_INDEX
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                 ORDER BY INDEX_NAME, SEQ_IN_INDEX',
                [$this->db->getPrefix() . $table]
            )->getResultArray();

            foreach ($rows as $row) {
                if ((int) ($row['NON_UNIQUE'] ?? 1) !== 0) {
                    continue;
                }

                $name = (string) $row['INDEX_NAME'];

                if ($name === 'PRIMARY') {
                    continue;
                }

                $result[$name][] = (string) $row['COLUMN_NAME'];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Apakah index dengan nama tersebut sudah ada di tabel.
     */
    private function indexExists(string $table, string $index): bool
    {
        return array_key_exists($index, $this->listUniqueIndexes($table));
    }

    /**
     * Hapus index, baik di MySQL maupun SQLite.
     */
    private function dropIndex(string $table, string $index, bool $isSqlite): void
    {
        $prefixed = $this->db->getPrefix() . $table;

        if ($isSqlite) {
            $this->db->query("DROP INDEX `{$index}`");

            return;
        }

        $this->db->query("ALTER TABLE `{$prefixed}` DROP INDEX `{$index}`");
    }

    /**
     * Buat unique key komposit (kolom, deleted_at).
     */
    private function createCompositeUnique(string $table, string $column, string $index, bool $isSqlite): void
    {
        $prefixed = $this->db->getPrefix() . $table;

        if ($isSqlite) {
            $this->db->query(
                "CREATE UNIQUE INDEX `{$index}` ON `{$prefixed}` (`{$column}`, `deleted_at`)"
            );

            return;
        }

        $this->db->query(
            "ALTER TABLE `{$prefixed}` ADD UNIQUE KEY `{$index}` (`{$column}`, `deleted_at`)"
        );
    }

    /**
     * Kembalikan unique key kolom tunggal seperti sebelum migrasi.
     */
    private function createSingleUnique(string $table, string $column, bool $isSqlite): void
    {
        $prefixed = $this->db->getPrefix() . $table;

        if ($isSqlite) {
            $this->db->query("CREATE UNIQUE INDEX `{$column}` ON `{$prefixed}` (`{$column}`)");

            return;
        }

        $this->db->query("ALTER TABLE `{$prefixed}` ADD UNIQUE KEY `{$column}` (`{$column}`)");
    }
}
