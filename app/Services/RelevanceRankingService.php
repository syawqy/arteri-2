<?php

declare(strict_types=1);

namespace App\Services;

/**
 * RelevanceRankingService
 *
 * Implements Saracevic-stratified relevance ranking adapted from
 * Fafalios et al. (2018) arXiv:1810.11049 — JCDL 2017.
 *
 * Three components (all normalized 0..1):
 *  1) relativeness — keyword match strength across uraian/noarsip/master names
 *  2) timeliness   — urgency relative to retention expiry (b = tanggal + retensi)
 *  3) relations    — co-occurrence of (kode, pencipta) — optional stat map
 *
 * Final score = w1*rel + w2*time + w3*rel  (w1+w2+w3 = 1, default 0.5/0.3/0.2)
 *
 * Mapping to Saracevic (1975/2007):
 *  - relativeness → System + Topical relevance
 *  - timeliness   → Situational relevance (retention task)
 *  - relations    → Cognitive + Situational (entity co-occurrence)
 */
class RelevanceRankingService
{
    public const DEFAULT_WEIGHTS = ['rel' => 0.5, 'time' => 0.3, 'relasi' => 0.2];

    /**
     * Rank an array of archive rows (as returned by ArsipModel::buildSearchQuery).
     * Enriches each row with ranking fields and sorts DESC by score.
     *
     * @param array  $rows     Raw rows (assoc arrays) with keys: uraian, noarsip, nobox, nama_pencipta, nama_pengolah, nama_kode, tanggal, retensi, b, f, kode, pencipta
     * @param string $keywords Keyword string (may contain spaces — split into tokens)
     * @param array  $weights  ['rel'=>float, 'time'=>float, 'relasi'=>float]
     * @param array  $cooccurrenceMap Optional map "kodeId:penciptaId" => count (for relations). Pass [] to disable.
     * @return array Ranked rows (same rows enriched with score, score_rel, score_time, score_relasi)
     */
    public function rank(array $rows, string $keywords = '', array $weights = [], array $cooccurrenceMap = []): array
    {
        if ($rows === []) {
            return [];
        }

        $w = array_merge(self::DEFAULT_WEIGHTS, $weights);
        // Normalize weights to sum 1
        $sum = max(0.0001, (float) $w['rel'] + (float) $w['time'] + (float) $w['relasi']);
        $w['rel'] /= $sum;
        $w['time'] /= $sum;
        $w['relasi'] /= $sum;

        $tokens = $this->tokenize($keywords);
        $maxRelCount = $cooccurrenceMap !== [] ? max($cooccurrenceMap) : 1;

        foreach ($rows as &$row) {
            $row['score_rel']    = $this->scoreRelativeness($row, $tokens);
            $row['score_time']   = $this->scoreTimeliness($row);
            $row['score_relasi'] = $this->scoreRelations($row, $cooccurrenceMap, (int) $maxRelCount);
            $row['score']        = round(
                $w['rel'] * $row['score_rel']
                + $w['time'] * $row['score_time']
                + $w['relasi'] * $row['score_relasi'],
                4
            );
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                // Tie-breaker: newer expiry first, then newer tanggal
                $ta = $a['tanggal'] ?? '';
                $tb = $b['tanggal'] ?? '';
                return strcmp((string) $tb, (string) $ta);
            }
            return ($a['score'] < $b['score']) ? 1 : -1;
        });

        return $rows;
    }

    /**
     * Tokens: lowercase, split on whitespace, strip empty, unique.
     */
    public function tokenize(string $keywords): array
    {
        $keywords = mb_strtolower(trim($keywords), 'UTF-8');
        if ($keywords === '') {
            return [];
        }
        $parts = preg_split('/\s+/u', $keywords) ?: [];
        $parts = array_values(array_unique(array_filter(array_map('trim', $parts))));
        return $parts;
    }

    /**
     * Relativeness 0..1:
     *  For each token, score per field with field weights:
     *   uraian exact substring=3, prefix=2, contains=1
     *   noarsip contains=2, nobox=1
     *   nama_pencipta/nama_pengolah/nama_kode contains=1.5
     *  Total divided by max achievable (tokens * 3) and capped at 1.
     */
    public function scoreRelativeness(array $row, array $tokens): float
    {
        if ($tokens === []) {
            return 0.0;
        }

        $uraian     = mb_strtolower((string) ($row['uraian'] ?? ''), 'UTF-8');
        $noarsip    = mb_strtolower((string) ($row['noarsip'] ?? ''), 'UTF-8');
        $nobox      = mb_strtolower((string) ($row['nobox'] ?? ''), 'UTF-8');
        $pencipta   = mb_strtolower((string) ($row['nama_pencipta'] ?? ''), 'UTF-8');
        $pengolah   = mb_strtolower((string) ($row['nama_pengolah'] ?? ''), 'UTF-8');
        $kodeNama   = mb_strtolower(trim((string) ($row['nama_kode'] ?? '') . ' ' . (string) ($row['nama'] ?? '')), 'UTF-8');

        $score = 0.0;
        foreach ($tokens as $tok) {
            $tokScore = 0.0;

            // uraian: strongest signal
            if ($uraian !== '' && mb_strpos($uraian, $tok) !== false) {
                // bonus if token at word boundary
                if (preg_match('/\b' . preg_quote($tok, '/') . '\b/u', $uraian)) {
                    $tokScore = max($tokScore, 3.0);
                } else {
                    $tokScore = max($tokScore, 1.5);
                }
            }
            if ($noarsip !== '' && mb_strpos($noarsip, $tok) !== false) {
                $tokScore = max($tokScore, 2.0);
            }
            if ($nobox !== '' && mb_strpos($nobox, $tok) !== false) {
                $tokScore = max($tokScore, 1.0);
            }
            if ($pencipta !== '' && mb_strpos($pencipta, $tok) !== false) {
                $tokScore = max($tokScore, 1.5);
            }
            if ($pengolah !== '' && mb_strpos($pengolah, $tok) !== false) {
                $tokScore = max($tokScore, 1.5);
            }
            if ($kodeNama !== '' && mb_strpos($kodeNama, $tok) !== false) {
                $tokScore = max($tokScore, 1.5);
            }

            $score += $tokScore;
        }

        $max = count($tokens) * 3.0;
        return $max > 0 ? min(1.0, $score / $max) : 0.0;
    }

    /**
     * Timeliness 0..1 based on retention expiry b (= tanggal + retensi years).
     * Uses derived fields b (YYYY-MM-DD) and f (sudah/belum) if present.
     *  - b == today  => 1.0
     *  - |b - today| / 365 decays: 1 / (1 + days/365)
     *  - fallback: linear decay from tanggal age if b missing.
     */
    public function scoreTimeliness(array $row): float
    {
        $today = new \DateTimeImmutable('today');

        $b = $row['b'] ?? null;
        if (is_string($b) && $b !== '' && $b !== '0000-00-00') {
            try {
                $expiry = new \DateTimeImmutable($b);
                $diffDays = (int) $today->diff($expiry)->days;
                // If expiry is in the past, same formula but slightly boosted for already-expired
                $f = $row['f'] ?? '';
                $base = 1.0 / (1.0 + $diffDays / 365.0);
                if ($f === 'sudah' || $expiry < $today) {
                    // Already expired: relevant for disposal tasks — mild boost
                    $base = min(1.0, $base + 0.08);
                }
                return round(min(1.0, max(0.0, $base)), 4);
            } catch (\Throwable $e) {
                // fall through to tanggal-based
            }
        }

        // Fallback: use tanggal age (newer = more timely, capped)
        $tanggal = $row['tanggal'] ?? null;
        if (is_string($tanggal) && $tanggal !== '' && $tanggal !== '0000-00-00') {
            try {
                $tgl = new \DateTimeImmutable($tanggal);
                $ageDays = (int) $tgl->diff($today)->days;
                // Newer documents slightly more timely; decay over ~5 years
                return round(1.0 / (1.0 + $ageDays / (365 * 3)), 4);
            } catch (\Throwable $e) {
                return 0.5;
            }
        }

        return 0.5;
    }

    /**
     * Relations 0..1 via co-occurrence map.
     * Key = "kodeId:penciptaId" (both cast to string).
     * Score = count / maxCount, 0 if missing.
     */
    public function scoreRelations(array $row, array $cooccurrenceMap, int $maxCount = 1): float
    {
        if ($cooccurrenceMap === [] || $maxCount <= 0) {
            return 0.0;
        }

        $kode     = (string) ($row['kode'] ?? '');
        $pencipta = (string) ($row['pencipta'] ?? '');

        if ($kode === '' || $pencipta === '') {
            return 0.0;
        }

        $key = $kode . ':' . $pencipta;
        $cnt = $cooccurrenceMap[$key] ?? 0;

        return $cnt > 0 ? min(1.0, $cnt / $maxCount) : 0.0;
    }

    /**
     * Build co-occurrence map from an array of rows (for batch).
     * Counts occurrences of each (kode, pencipta) pair.
     *
     * @return array<string, int>
     */
    public function buildCooccurrenceMap(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $kode     = (string) ($row['kode'] ?? '');
            $pencipta = (string) ($row['pencipta'] ?? '');
            if ($kode === '' || $pencipta === '') {
                continue;
            }
            $key = $kode . ':' . $pencipta;
            $map[$key] = ($map[$key] ?? 0) + 1;
        }
        return $map;
    }
}
