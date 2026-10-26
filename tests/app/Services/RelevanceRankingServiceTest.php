<?php

namespace Tests\App\Services;

use App\Services\RelevanceRankingService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class RelevanceRankingServiceTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRankWithKeywords(): void
    {
        $service = new RelevanceRankingService();

        $rows = [
            [
                'uraian'     => 'Arsip kontrak kerja',
                'noarsip'    => 'ARS-001',
                'tanggal'    => '2025-01-15',
                'retensi'    => '10',
                'kode'       => 'K01',
                'pencipta'   => 'P001',
            ],
            [
                'uraian'     => 'Laporan bulanan',
                'noarsip'    => 'ARS-002',
                'tanggal'    => '2025-06-01',
                'retensi'    => '3',
                'kode'       => 'K02',
                'pencipta'   => 'P002',
            ],
        ];

        $result = $service->rank($rows, 'kontrak kerja');

        $this->assertCount(2, $result);
        // First result should have higher score due to keyword match
        $this->assertGreaterThan(0, $result[0]['score']);
    }

    public function testRankWithEmptyKeywords(): void
    {
        $service = new RelevanceRankingService();

        $rows = [
            [
                'uraian'     => 'Arsip kontrak kerja',
                'noarsip'    => 'ARS-001',
                'tanggal'    => '2025-01-15',
                'retensi'    => '10',
                'kode'       => 'K01',
                'pencipta'   => 'P001',
            ],
        ];

        $result = $service->rank($rows, '');

        $this->assertCount(1, $result);
    }

    public function testRankWithCustomWeights(): void
    {
        $service = new RelevanceRankingService();

        $rows = [
            [
                'uraian'     => 'Arsip kontrak kerja',
                'noarsip'    => 'ARS-001',
                'tanggal'    => '2025-01-15',
                'retensi'    => '10',
                'kode'       => 'K01',
                'pencipta'   => 'P001',
            ],
        ];

        $result = $service->rank($rows, 'kontrak', ['rel' => 0.3, 'time' => 0.4, 'relasi' => 0.3]);

        $this->assertCount(1, $result);
        $this->assertIsFloat($result[0]['score']);
    }

    public function testEmptyRowsReturnsEmptyArray(): void
    {
        $service = new RelevanceRankingService();

        $result = $service->rank([], 'test');

        $this->assertEmpty($result);
    }

    public function testScoreRelationsWithCooccurrenceMap(): void
    {
        $service = new RelevanceRankingService();

        $cooccurrenceMap = [
            'K01:P001' => 5,
            'K02:P002' => 2,
        ];

        $row = [
            'kode'   => 'K01',
            'pencipta' => 'P001',
        ];

        $score = $service->scoreRelations($row, $cooccurrenceMap, 10);

        $this->assertEquals(0.5, $score);
    }

    public function testScoreRelationsWithMissingKey(): void
    {
        $service = new RelevanceRankingService();

        $cooccurrenceMap = [
            'K01:P001' => 5,
        ];

        $row = [
            'kode'   => 'K02',
            'pencipta' => 'P002',
        ];

        $score = $service->scoreRelations($row, $cooccurrenceMap, 10);

        $this->assertEquals(0.0, $score);
    }

    public function testBuildCooccurrenceMap(): void
    {
        $service = new RelevanceRankingService();

        $rows = [
            [
                'kode'   => 'K01',
                'pencipta' => 'P001',
            ],
            [
                'kode'   => 'K01',
                'pencipta' => 'P001',
            ],
            [
                'kode'   => 'K02',
                'pencipta' => 'P002',
            ],
        ];

        $map = $service->buildCooccurrenceMap($rows);

        $this->assertArrayHasKey('K01:P001', $map);
        $this->assertArrayHasKey('K02:P002', $map);
        $this->assertEquals(2, $map['K01:P001']);
        $this->assertEquals(1, $map['K02:P002']);
    }

    public function testEmptyCooccurrenceMap(): void
    {
        $service = new RelevanceRankingService();

        $row = [
            'kode'   => 'K01',
            'pencipta' => 'P001',
        ];

        $score = $service->scoreRelations($row, [], 10);

        $this->assertEquals(0.0, $score);
    }

    public function testEmptyPenciptaReturnsZero(): void
    {
        $service = new RelevanceRankingService();

        $cooccurrenceMap = [
            'K01:P001' => 5,
        ];

        $row = [
            'kode'   => 'K01',
            'pencipta' => '',
        ];

        $score = $service->scoreRelations($row, $cooccurrenceMap, 10);

        $this->assertEquals(0.0, $score);
    }
}
