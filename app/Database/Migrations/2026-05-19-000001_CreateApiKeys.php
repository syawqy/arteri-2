<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeys extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'key_prefix'  => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => false],
            'key_hash'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => false],
            'created_by'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'rate_limit'  => ['type' => 'INT', 'unsigned' => true, 'default' => 60],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'expires_at'  => ['type' => 'DATETIME', 'null' => true],
            'last_used_at'=> ['type' => 'DATETIME', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => false],
            'revoked_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('key_hash');
        $this->forge->addKey('key_prefix');
        $this->forge->createTable('api_keys', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('api_keys', true);
    }
}
