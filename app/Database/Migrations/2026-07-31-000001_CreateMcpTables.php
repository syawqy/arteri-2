<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMcpTables extends Migration
{
    public function up(): void
    {
        // ai_ingestion_queue
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'filename'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_path' => ['type' => 'TEXT', 'null' => true],
            'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'file_size'     => ['type' => 'INT', 'unsigned' => true],
            'ocr_text'      => ['type' => 'LONGTEXT', 'null' => true],
            'raw_metadata'  => ['type' => 'JSON', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending','processing','completed','failed','queued_verification'], 'default' => 'pending'],
            'error_message' => ['type' => 'TEXT', 'null' => true],
            'processed_by'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'uploaded_by'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'    => ['type' => 'DATETIME'],
            'updated_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('uploaded_by');
        $this->forge->createTable('ai_ingestion_queue', true);

        // ai_verification_queue
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'ingestion_id'  => ['type' => 'INT', 'unsigned' => true],
            'field_name'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'ai_value'      => ['type' => 'TEXT', 'null' => true],
            'ai_confidence' => ['type' => 'DECIMAL', 'constraint' => '5,4'],
            'human_value'   => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['pending','approved','rejected','overridden'], 'default' => 'pending'],
            'verified_by'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'verified_at'   => ['type' => 'DATETIME', 'null' => true],
            'notes'         => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('ingestion_id');
        $this->forge->addKey('status');
        $this->forge->createTable('ai_verification_queue', true);

        // ai_classifications
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'         => ['type' => 'INT', 'unsigned' => true],
            'suggested_kode'   => ['type' => 'VARCHAR', 'constraint' => 10],
            'confidence'       => ['type' => 'DECIMAL', 'constraint' => '5,4'],
            'reasoning'        => ['type' => 'TEXT', 'null' => true],
            'matched_rules'    => ['type' => 'JSON', 'null' => true],
            'suggested_series' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['suggested','approved','rejected','applied'], 'default' => 'suggested'],
            'approved_by'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'approved_at'      => ['type' => 'DATETIME', 'null' => true],
            'created_at'       => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('status');
        $this->forge->createTable('ai_classifications', true);

        // retention_schedules
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kode_klas'       => ['type' => 'VARCHAR', 'constraint' => 10],
            'nama_jadwal'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'retensi_aktif'   => ['type' => 'INT', 'unsigned' => true],
            'retensi_inaktif' => ['type' => 'INT', 'unsigned' => true],
            'jenis_disposisi' => ['type' => 'ENUM', 'constraint' => ['simpan_permanen','musnah','serahkan_ke_arsip_nasional'], 'default' => 'musnah'],
            'dasar_hukum'     => ['type' => 'TEXT', 'null' => true],
            'keterangan'      => ['type' => 'TEXT', 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode_klas');
        $this->forge->createTable('retention_schedules', true);

        // retention_actions
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'        => ['type' => 'INT', 'unsigned' => true],
            'schedule_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'action_type'     => ['type' => 'ENUM', 'constraint' => ['propose_destruction','propose_transfer','extend_retention','apply_legal_hold']],
            'reason'          => ['type' => 'TEXT', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draft','pending_approval','approved','rejected','completed'], 'default' => 'draft'],
            'prepared_by'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'approved_by'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'approved_at'     => ['type' => 'DATETIME', 'null' => true],
            'berita_acara_no' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'form_metadata'   => ['type' => 'JSON', 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('status');
        $this->forge->createTable('retention_actions', true);

        // legal_holds
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'    => ['type' => 'INT', 'unsigned' => true],
            'reason'      => ['type' => 'TEXT'],
            'case_ref'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'imposed_by'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'imposed_at'  => ['type' => 'DATETIME'],
            'released_by' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'released_at' => ['type' => 'DATETIME', 'null' => true],
            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('is_active');
        $this->forge->createTable('legal_holds', true);

        // document_access_log
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'arsip_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'accessed_by'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'access_type'   => ['type' => 'ENUM', 'constraint' => ['view','edit','download','delete','classify','search','export']],
            'access_source' => ['type' => 'VARCHAR', 'constraint' => 50],
            'details'       => ['type' => 'JSON', 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'accessed_at'   => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('arsip_id');
        $this->forge->addKey('accessed_by');
        $this->forge->addKey('accessed_at');
        $this->forge->createTable('document_access_log', true);

        // migration_jobs
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'source_type'     => ['type' => 'VARCHAR', 'constraint' => 50],
            'source_config'   => ['type' => 'JSON'],
            'status'          => ['type' => 'ENUM', 'constraint' => ['created','analyzing','analyzed','mapping','mapped','importing','completed','failed'], 'default' => 'created'],
            'total_items'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'processed_items' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'error_items'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'field_mapping'   => ['type' => 'JSON', 'null' => true],
            'error_log'       => ['type' => 'LONGTEXT', 'null' => true],
            'started_by'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at'      => ['type' => 'DATETIME'],
            'updated_at'      => ['type' => 'DATETIME'],
            'completed_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->createTable('migration_jobs', true);

        // migration_items
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'job_id'          => ['type' => 'INT', 'unsigned' => true],
            'source_path'     => ['type' => 'TEXT'],
            'filename'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_size'       => ['type' => 'INT', 'unsigned' => true],
            'mime_type'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'raw_metadata'    => ['type' => 'JSON', 'null' => true],
            'mapped_metadata' => ['type' => 'JSON', 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending','imported','skipped','failed'], 'default' => 'pending'],
            'arsip_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'error_message'   => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('job_id');
        $this->forge->addKey('status');
        $this->forge->createTable('migration_items', true);
    }

    public function down(): void
    {
        $tables = [
            'migration_items', 'migration_jobs', 'document_access_log',
            'legal_holds', 'retention_actions', 'retention_schedules',
            'ai_classifications', 'ai_verification_queue', 'ai_ingestion_queue',
        ];
        foreach ($tables as $table) {
            $this->forge->dropTable($table, true);
        }
    }
}
