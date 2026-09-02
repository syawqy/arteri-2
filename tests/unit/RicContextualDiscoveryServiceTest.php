<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RicContextualDiscoveryService;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test untuk RicContextualDiscoveryService.
 */
final class RicContextualDiscoveryServiceTest extends TestCase
{
    private RicContextualDiscoveryService $service;

    protected function setUp(): void
    {
        $this->service = new RicContextualDiscoveryService();
    }

    public function testComputeAffinitySameAgentAndActivityGivesHighestScore(): void
    {
        $seed = [
            'pencipta' => 'Biro Keuangan',
            'unit_pengolah' => 'Bagian Akuntansi',
            'kode' => 'KU.01.01',
            'tanggal' => '2026-01-01',
        ];

        $candidateExact = [
            'pencipta' => 'Biro Keuangan',
            'unit_pengolah' => 'Bagian Akuntansi',
            'kode' => 'KU.01.01',
            'tanggal' => '2026-01-05',
        ];

        $candidateDifferent = [
            'pencipta' => 'Biro Umum',
            'unit_pengolah' => 'Bagian RT',
            'kode' => 'UM.01.01',
            'tanggal' => '2020-01-01',
        ];

        $scoreExact = $this->service->computeAffinity($seed, $candidateExact);
        $scoreDiff = $this->service->computeAffinity($seed, $candidateDifferent);

        $this->assertGreaterThan($scoreDiff['cas_score'], $scoreExact['cas_score']);
        $this->assertEquals(1.0, $scoreExact['agent_affinity']);
        $this->assertEquals(1.0, $scoreExact['activity_affinity']);
        $this->assertGreaterThan(0.9, $scoreExact['temporal_affinity']);
    }

    public function testComputeAffinitySameClusterCodesGivesPartialScore(): void
    {
        $seed = [
            'pencipta' => 'Biro Keuangan',
            'unit_pengolah' => 'Bagian Akuntansi',
            'kode' => 'KU.01.01',
            'tanggal' => '2026-01-01',
        ];

        $candidateCluster = [
            'pencipta' => 'Biro Keuangan',
            'unit_pengolah' => 'Bagian Verifikasi',
            'kode' => 'KU.02.01', // Sama rumpun KU
            'tanggal' => '2026-01-01',
        ];

        $scoreCluster = $this->service->computeAffinity($seed, $candidateCluster);

        $this->assertEquals(0.6, $scoreCluster['agent_affinity']); // Hanya pencipta yang cocok
        $this->assertEquals(0.5, $scoreCluster['activity_affinity']); // Rumpun urusan sama
    }

    public function testGenerateJsonLdGraphOutputsStandardRiCOStructure(): void
    {
        $sampleArsip = [
            'id' => 101,
            'noarsip' => 'ARS-2026-001',
            'uraian' => 'Laporan Tahunan Keuangan',
            'tanggal' => '2026-01-15',
            'pencipta' => 'Biro Keuangan',
            'unit_pengolah' => 'Bagian Akuntansi',
            'kode' => 'KU.01',
            'lokasi' => 'Gedung A Lantai 2',
        ];

        $sampleRelated = [
            [
                'id' => 102,
                'noarsip' => 'ARS-2026-002',
                'uraian' => 'Laporan Audit Triwulan',
                'cas_score' => 0.875,
            ]
        ];

        $jsonLd = $this->service->generateJsonLdGraph($sampleArsip, $sampleRelated, 'http://localhost:8082');

        $this->assertIsArray($jsonLd);
        $this->assertArrayHasKey('@context', $jsonLd);
        $this->assertArrayHasKey('@graph', $jsonLd);
        $this->assertEquals('https://www.ica.org/standards/RiC/ontology#', $jsonLd['@context']['ric']);

        $mainNode = $jsonLd['@graph'][0];
        $this->assertEquals('ric:Record', $mainNode['@type']);
        $this->assertEquals('Laporan Tahunan Keuangan', $mainNode['ric:title']);
        $this->assertArrayHasKey('ric:hasCreator', $mainNode);
        $this->assertArrayHasKey('ric:isContextuallyRelatedTo', $mainNode);
        $this->assertCount(1, $mainNode['ric:isContextuallyRelatedTo']);
    }
}
