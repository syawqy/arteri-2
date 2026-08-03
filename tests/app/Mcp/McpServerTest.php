<?php

declare(strict_types=1);

namespace Tests\App\Mcp;

use CodeIgniter\Test\CIUnitTestCase;

class McpServerTest extends CIUnitTestCase
{
    private $testDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDb = \Config\Database::connect();

        // Create tables via Forge (handles prefix correctly)
        if (!$this->testDb->tableExists('master_user')) {
            $forge = \Config\Database::forge();
            $forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'username'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'password'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'tipe'       => ['type' => 'TEXT', 'null' => false, 'default' => 'user'],
                'akses_klas' => ['type' => 'TEXT', 'null' => false, 'default' => ''],
                'akses_modul' => ['type' => 'TEXT', 'null' => false, 'default' => ''],
                'deleted_at'  => ['type' => 'TEXT', 'null' => true],
            ]);
            $forge->addPrimaryKey('id');
            $forge->addUniqueKey('username');
            $forge->createTable('master_user', true);
        }

        $mcpTables = [
            'ai_ingestion_queue' => [
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'filename'      => ['type' => 'VARCHAR', 'constraint' => 255],
                'original_path' => ['type' => 'TEXT', 'null' => true],
                'mime_type'     => ['type' => 'VARCHAR', 'constraint' => 100],
                'file_size'     => ['type' => 'INT', 'unsigned' => true],
                'ocr_text'      => ['type' => 'TEXT', 'null' => true],
                'raw_metadata'  => ['type' => 'TEXT', 'null' => true],
                'status'        => ['type' => 'TEXT', 'default' => 'pending'],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'processed_by'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'uploaded_by'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'created_at'    => ['type' => 'TEXT'],
                'updated_at'    => ['type' => 'TEXT'],
            ],
            'ai_verification_queue' => [
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'ingestion_id'  => ['type' => 'INT', 'unsigned' => true],
                'field_name'    => ['type' => 'VARCHAR', 'constraint' => 100],
                'ai_value'      => ['type' => 'TEXT', 'null' => true],
                'ai_confidence' => ['type' => 'REAL'],
                'human_value'   => ['type' => 'TEXT', 'null' => true],
                'status'        => ['type' => 'TEXT', 'default' => 'pending'],
                'verified_by'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'verified_at'   => ['type' => 'TEXT', 'null' => true],
                'notes'         => ['type' => 'TEXT', 'null' => true],
                'created_at'    => ['type' => 'TEXT'],
            ],
            'ai_classifications' => [
                'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'arsip_id'         => ['type' => 'INT', 'unsigned' => true],
                'suggested_kode'   => ['type' => 'VARCHAR', 'constraint' => 10],
                'confidence'       => ['type' => 'REAL'],
                'reasoning'        => ['type' => 'TEXT', 'null' => true],
                'matched_rules'    => ['type' => 'TEXT', 'null' => true],
                'suggested_series' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status'           => ['type' => 'TEXT', 'default' => 'suggested'],
                'approved_by'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'approved_at'      => ['type' => 'TEXT', 'null' => true],
                'created_at'       => ['type' => 'TEXT'],
            ],
            'retention_schedules' => [
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'kode_klas'       => ['type' => 'VARCHAR', 'constraint' => 10],
                'nama_jadwal'     => ['type' => 'VARCHAR', 'constraint' => 255],
                'retensi_aktif'   => ['type' => 'INT', 'unsigned' => true],
                'retensi_inaktif' => ['type' => 'INT', 'unsigned' => true],
                'jenis_disposisi' => ['type' => 'TEXT', 'default' => 'musnah'],
                'dasar_hukum'     => ['type' => 'TEXT', 'null' => true],
                'keterangan'      => ['type' => 'TEXT', 'null' => true],
                'is_active'       => ['type' => 'INT', 'default' => 1],
                'created_at'      => ['type' => 'TEXT'],
                'updated_at'      => ['type' => 'TEXT'],
            ],
            'retention_actions' => [
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'arsip_id'        => ['type' => 'INT', 'unsigned' => true],
                'schedule_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'action_type'     => ['type' => 'TEXT'],
                'reason'          => ['type' => 'TEXT', 'null' => true],
                'status'          => ['type' => 'TEXT', 'default' => 'draft'],
                'prepared_by'     => ['type' => 'VARCHAR', 'constraint' => 255],
                'approved_by'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'approved_at'     => ['type' => 'TEXT', 'null' => true],
                'berita_acara_no' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'form_metadata'   => ['type' => 'TEXT', 'null' => true],
                'created_at'      => ['type' => 'TEXT'],
                'updated_at'      => ['type' => 'TEXT'],
            ],
            'legal_holds' => [
                'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'arsip_id'    => ['type' => 'INT', 'unsigned' => true],
                'reason'      => ['type' => 'TEXT'],
                'case_ref'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'imposed_by'  => ['type' => 'VARCHAR', 'constraint' => 255],
                'imposed_at'  => ['type' => 'TEXT'],
                'released_by' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'released_at' => ['type' => 'TEXT', 'null' => true],
                'is_active'   => ['type' => 'INT', 'default' => 1],
            ],
            'document_access_log' => [
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'arsip_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'accessed_by'   => ['type' => 'VARCHAR', 'constraint' => 255],
                'access_type'   => ['type' => 'TEXT'],
                'access_source' => ['type' => 'VARCHAR', 'constraint' => 50],
                'details'       => ['type' => 'TEXT', 'null' => true],
                'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
                'accessed_at'   => ['type' => 'TEXT'],
            ],
            'migration_jobs' => [
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'source_type'     => ['type' => 'VARCHAR', 'constraint' => 50],
                'source_config'   => ['type' => 'TEXT'],
                'status'          => ['type' => 'TEXT', 'default' => 'created'],
                'total_items'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'processed_items' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'error_items'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
                'field_mapping'   => ['type' => 'TEXT', 'null' => true],
                'error_log'       => ['type' => 'TEXT', 'null' => true],
                'started_by'      => ['type' => 'VARCHAR', 'constraint' => 255],
                'created_at'      => ['type' => 'TEXT'],
                'updated_at'      => ['type' => 'TEXT'],
                'completed_at'    => ['type' => 'TEXT', 'null' => true],
            ],
            'migration_items' => [
                'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'job_id'          => ['type' => 'INT', 'unsigned' => true],
                'source_path'     => ['type' => 'TEXT'],
                'filename'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'file_size'       => ['type' => 'INT', 'unsigned' => true],
                'mime_type'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'raw_metadata'    => ['type' => 'TEXT', 'null' => true],
                'mapped_metadata' => ['type' => 'TEXT', 'null' => true],
                'status'          => ['type' => 'TEXT', 'default' => 'pending'],
                'arsip_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'error_message'   => ['type' => 'TEXT', 'null' => true],
                'created_at'      => ['type' => 'TEXT'],
            ],
        ];

        $forge = \Config\Database::forge();
        foreach ($mcpTables as $table => $fields) {
            if (!$this->testDb->tableExists($table)) {
                $forge->addField($fields);
                $forge->addPrimaryKey('id');
                $forge->createTable($table, true);
            }
        }
    }

    public function testMcpTablesExist(): void
    {
        $tables = [
            'ai_ingestion_queue',
            'ai_verification_queue',
            'ai_classifications',
            'retention_schedules',
            'retention_actions',
            'legal_holds',
            'document_access_log',
            'migration_jobs',
            'migration_items',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(
                $this->testDb->tableExists($table),
                "Table {$table} should exist"
            );
        }
    }

    public function testAccessGuardBlocksUnauthenticated(): void
    {
        $guard = new \App\Mcp\Services\AccessGuard();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Authentication required');
        $guard->requireAuth();
    }

    public function testConfidenceScorerThresholds(): void
    {
        $this->assertTrue(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.5));
        $this->assertFalse(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.8));
        $this->assertTrue(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.74));
        $this->assertFalse(\App\Mcp\Services\ConfidenceScorer::needsVerification(0.75));
    }

    public function testDocumentParserExtractsDate(): void
    {
        $parser = new \App\Mcp\Services\DocumentParser();
        $result = $parser->parseFromText("Surat Masuk\nNomor: 001/SM/2026\nTanggal: 15/06/2026\nPerihal: Laporan Keuangan");

        $this->assertArrayHasKey('tanggal', $result['metadata']);
        $this->assertEquals('2026-06-15', $result['metadata']['tanggal']);
    }

    public function testDocumentParserExtractsNoArsip(): void
    {
        $parser = new \App\Mcp\Services\DocumentParser();
        $result = $parser->parseFromText("Dokumen 005/SK/2026 tentang Surat Keputusan");

        $this->assertArrayHasKey('noarsip', $result['metadata']);
        $this->assertEquals('005/SK/2026', $result['metadata']['noarsip']);
    }

    public function testOcrQualityScoreBlank(): void
    {
        $this->assertEquals(0.0, \App\Mcp\Services\ConfidenceScorer::ocrQualityScore(''));
        $this->assertEquals(0.1, \App\Mcp\Services\ConfidenceScorer::ocrQualityScore('abc'));
    }

    public function testOcrQualityScoreGood(): void
    {
        $goodText = str_repeat('This is a well-formed document with proper text. ', 20);
        $score = \App\Mcp\Services\ConfidenceScorer::ocrQualityScore($goodText);
        $this->assertGreaterThan(0.5, $score);
    }

    public function testFieldConfidenceMethods(): void
    {
        $this->assertEquals(0.0, \App\Mcp\Services\ConfidenceScorer::fieldConfidence('', 'heuristic'));
        $this->assertEquals(0.9, \App\Mcp\Services\ConfidenceScorer::fieldConfidence('001/SM/2026', 'regex_pattern'));
        $this->assertEquals(0.85, \App\Mcp\Services\ConfidenceScorer::fieldConfidence('Some text', 'ocr_structured'));
        $this->assertEquals(1.0, \App\Mcp\Services\ConfidenceScorer::fieldConfidence('Manual input', 'user_input'));
    }

    public function testOverallConfidence(): void
    {
        $this->assertEquals(0.0, \App\Mcp\Services\ConfidenceScorer::overallConfidence([]));
        $this->assertEquals(0.9, \App\Mcp\Services\ConfidenceScorer::overallConfidence([0.9]));
        $this->assertEquals(0.8, \App\Mcp\Services\ConfidenceScorer::overallConfidence([0.8, 0.8]));
    }

    public function testMcpConfigValidTransport(): void
    {
        $this->assertTrue(\App\Mcp\Config\McpConfig::isValidTransport('stdio'));
        $this->assertTrue(\App\Mcp\Config\McpConfig::isValidTransport('http'));
        $this->assertFalse(\App\Mcp\Config\McpConfig::isValidTransport('grpc'));
        $this->assertFalse(\App\Mcp\Config\McpConfig::isValidTransport(''));
    }

    public function testMcpConfigConstants(): void
    {
        $this->assertEquals('Arteri Archive Management', \App\Mcp\Config\McpConfig::SERVER_NAME);
        $this->assertEquals('1.0.0', \App\Mcp\Config\McpConfig::SERVER_VERSION);
        $this->assertEquals(50, \App\Mcp\Config\McpConfig::PAGINATION_LIMIT);
    }
}
