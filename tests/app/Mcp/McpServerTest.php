<?php

declare(strict_types=1);

namespace Tests\App\Mcp;

use CodeIgniter\Test\CIUnitTestCase;

class McpServerTest extends CIUnitTestCase
{
    public function testMcpTablesExist(): void
    {
        $db = \Config\Database::connect();

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
                $db->tableExists($table),
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
