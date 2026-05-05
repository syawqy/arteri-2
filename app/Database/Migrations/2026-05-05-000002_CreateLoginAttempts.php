<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginAttempts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'attempted_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['username', 'attempted_at']);
        $this->forge->createTable('login_attempts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('login_attempts', true);
    }
}
