<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChatTables extends Migration
{
    public function up(): void
    {
        // chat_sessions
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 255, 'default' => 'New Chat'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'updated_at']);
        $this->forge->createTable('chat_sessions', true);

        // chat_messages
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'session_id'    => ['type' => 'INT', 'unsigned' => true],
            'role'          => ['type' => 'ENUM', 'constraint' => ['user', 'assistant', 'tool', 'system']],
            'content'       => ['type' => 'LONGTEXT', 'null' => true],
            'tool_calls'    => ['type' => 'JSON', 'null' => true],
            'tool_call_id'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'tool_name'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'model'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'usage'         => ['type' => 'JSON', 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['session_id', 'created_at']);
        $this->forge->addForeignKey('session_id', 'chat_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('chat_messages', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('chat_messages', true);
        $this->forge->dropTable('chat_sessions', true);
    }
}
