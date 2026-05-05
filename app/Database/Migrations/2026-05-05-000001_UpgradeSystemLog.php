<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpgradeSystemLog extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('system_log', [
            'aksi'       => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false, 'after' => 'kode_transaksi'],
            'tabel'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'aksi'],
            'record_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'tabel'],
            'detail'     => ['type' => 'TEXT', 'null' => true, 'after' => 'record_id'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true, 'after' => 'detail'],
        ]);

        $this->forge->addKey('aksi');
    }

    public function down(): void
    {
    }
}
