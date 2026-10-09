<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Table statistik ko-okurensi (kode, pencipta) untuk komponen relations
 * pada ranking Saracevic (Fafalios 1810.11049 hybrid SQL).
 *
 * Diisi via: php spark db:seed SaracevicRankingSeeder (refresh) atau manual:
 *   INSERT INTO stat_kode_pencipta (kode, pencipta, cnt)
 *   SELECT kode, pencipta, COUNT(*) FROM data_arsip WHERE deleted_at IS NULL GROUP BY kode, pencipta
 */
class CreateStatKodePencipta extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'kode'     => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'pencipta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'cnt'      => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 0],
        ]);
        $this->forge->addKey(['kode', 'pencipta'], true);
        $this->forge->addKey('cnt');
        $this->forge->createTable('stat_kode_pencipta', true);

        // Index tambahan untuk join cepat
        try {
            $this->db->query('CREATE INDEX IF NOT EXISTS idx_stat_cnt ON stat_kode_pencipta(cnt)');
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('stat_kode_pencipta', true);
    }
}
