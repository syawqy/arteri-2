<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tambahkan kolom soft-delete `deleted_at` ke semua tabel data utama,
 * sehingga penghapusan dapat dipulihkan (trash/recovery, task 7b).
 *
 * Tabel infrastruktur (system_log, login_attempts, api_keys) sengaja
 * tidak diberi soft-delete.
 */
class AddSoftDeletes extends Migration
{
    /**
     * Tabel yang mendapat kolom soft-delete.
     *
     * @var list<string>
     */
    private array $tables = [
        'master_kode',
        'master_lokasi',
        'master_media',
        'master_pencipta',
        'master_pengolah',
        'master_user',
        'data_arsip',
        'sirkulasi',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! $this->db->fieldExists('deleted_at', $table)) {
                $this->forge->addColumn($table, [
                    'deleted_at' => ['type' => 'DATETIME', 'null' => true, 'default' => null],
                ]);
            }

            // Index untuk mempercepat scoping (deleted_at IS NULL) dan purge.
            try {
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `idx_deleted_at` (`deleted_at`)");
            } catch (\Throwable $e) {
                // Index sudah ada — abaikan (idempotent).
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            try {
                $this->db->query("ALTER TABLE `{$table}` DROP INDEX `idx_deleted_at`");
            } catch (\Throwable $e) {
                // Index tidak ada — abaikan.
            }

            if ($this->db->fieldExists('deleted_at', $table)) {
                $this->forge->dropColumn($table, 'deleted_at');
            }
        }
    }
}
