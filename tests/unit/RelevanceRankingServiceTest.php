<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\RelevanceRankingService;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit test — no CIUnitTestCase, no DB, no Services.
 * Covers Saracevic-stratified ranking (Fafalios 1810.11049 adapted).
 */
final class RelevanceRankingServiceTest extends TestCase
{
    private RelevanceRankingService $svc;

    protected function setUp(): void
    {
        $this->svc = new RelevanceRankingService();
    }

    public function testTokenize(): void
    {
        $this->assertSame(['rekrutmen', 'pegawai'], $this->svc->tokenize('  Rekrutmen   Pegawai  '));
        $this->assertSame([], $this->svc->tokenize(''));
        $this->assertSame(['sdm'], $this->svc->tokenize('SDM'));
        $this->assertSame(['a', 'b'], $this->svc->tokenize('a a b')); // unique
    }

    public function testRelativenessExactMatchScoresHigher(): void
    {
        $rowStrong = ['uraian' => 'Rekrutmen Pegawai tahun 2024', 'noarsip' => 'ARS/001/24', 'nama_pencipta' => 'Bidang Kepegawaian'];
        $rowWeak   = ['uraian' => 'Laporan keuangan audited', 'noarsip' => 'ARS/002/24', 'nama_pencipta' => 'Bidang Keuangan'];

        $scoreStrong = $this->svc->scoreRelativeness($rowStrong, ['rekrutmen']);
        $scoreWeak   = $this->svc->scoreRelativeness($rowWeak, ['rekrutmen']);

        $this->assertGreaterThan($scoreWeak, $scoreStrong);
        $this->assertGreaterThanOrEqual(0.0, $scoreStrong);
        $this->assertLessThanOrEqual(1.0, $scoreStrong);
    }

    public function testRelativenessEmptyTokensReturnsZero(): void
    {
        $this->assertSame(0.0, $this->svc->scoreRelativeness(['uraian' => 'anything'], []));
    }

    public function testTimelinessExpiryTodayScoresOne(): void
    {
        $today = date('Y-m-d');
        $score = $this->svc->scoreTimeliness(['b' => $today, 'f' => 'belum', 'tanggal' => $today]);
        $this->assertGreaterThanOrEqual(0.95, $score);
    }

    public function testTimelinessOldExpiryDecays(): void
    {
        $old = date('Y-m-d', strtotime('-5 years'));
        $scoreOld = $this->svc->scoreTimeliness(['b' => $old, 'f' => 'sudah', 'tanggal' => $old]);
        $today = date('Y-m-d');
        $scoreToday = $this->svc->scoreTimeliness(['b' => $today, 'f' => 'belum', 'tanggal' => $today]);
        $this->assertLessThan($scoreToday, $scoreOld);
    }

    public function testRelations(): void
    {
        $map = ['1:1' => 10, '1:2' => 5, '2:1' => 2];
        $this->assertEqualsWithDelta(1.0, $this->svc->scoreRelations(['kode' => '1', 'pencipta' => '1'], $map, 10), 0.01);
        $this->assertEqualsWithDelta(0.5, $this->svc->scoreRelations(['kode' => '1', 'pencipta' => '2'], $map, 10), 0.01);
        $this->assertSame(0.0, $this->svc->scoreRelations(['kode' => '9', 'pencipta' => '9'], $map, 10));
        $this->assertSame(0.0, $this->svc->scoreRelations(['kode' => '1', 'pencipta' => '1'], [], 0));
    }

    public function testBuildCooccurrenceMap(): void
    {
        $rows = [
            ['kode' => '1', 'pencipta' => '1'],
            ['kode' => '1', 'pencipta' => '1'],
            ['kode' => '2', 'pencipta' => '1'],
        ];
        $map = $this->svc->buildCooccurrenceMap($rows);
        $this->assertSame(2, $map['1:1']);
        $this->assertSame(1, $map['2:1']);
    }

    public function testRankSortsDescByScore(): void
    {
        $rows = [
            ['id' => 1, 'uraian' => 'Laporan keuangan', 'noarsip' => 'A1', 'tanggal' => '2020-01-01', 'kode' => '1', 'pencipta' => '1', 'nama_pencipta' => 'Keuangan', 'b' => '2025-01-01', 'f' => 'sudah'],
            ['id' => 2, 'uraian' => 'Rekrutmen pegawai', 'noarsip' => 'A2', 'tanggal' => date('Y-m-d'), 'kode' => '1', 'pencipta' => '1', 'nama_pencipta' => 'Kepegawaian', 'b' => date('Y-m-d'), 'f' => 'belum'],
            ['id' => 3, 'uraian' => 'Rekrutmen pegawai dan mutasi', 'noarsip' => 'A3', 'tanggal' => date('Y-m-d'), 'kode' => '1', 'pencipta' => '1', 'nama_pencipta' => 'Kepegawaian', 'b' => date('Y-m-d'), 'f' => 'belum'],
        ];

        $ranked = $this->svc->rank($rows, 'rekrutmen pegawai');

        $this->assertCount(3, $ranked);
        $this->assertNotSame(1, $ranked[0]['id']);
        $this->assertArrayHasKey('score', $ranked[0]);
        $this->assertArrayHasKey('score_rel', $ranked[0]);
        $this->assertArrayHasKey('score_time', $ranked[0]);
        $this->assertArrayHasKey('score_relasi', $ranked[0]);
    }

    public function testRankEmptyRows(): void
    {
        $this->assertSame([], $this->svc->rank([], 'anything'));
    }

    public function testRankWeightsNormalized(): void
    {
        $rows = [
            ['id' => 1, 'uraian' => 'rekrutmen', 'noarsip' => 'A1', 'tanggal' => date('Y-m-d'), 'kode' => '1', 'pencipta' => '1', 'b' => date('Y-m-d'), 'f' => 'belum'],
        ];
        $a = $this->svc->rank($rows, 'rekrutmen', ['rel' => 0.5, 'time' => 0.3, 'relasi' => 0.2]);
        $b = $this->svc->rank($rows, 'rekrutmen', ['rel' => 5, 'time' => 3, 'relasi' => 2]);
        $this->assertEqualsWithDelta($a[0]['score'], $b[0]['score'], 0.001);
    }
}
