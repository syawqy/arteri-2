#!/usr/bin/env php
<?php
// rank_smoke_pdo.php — verify SQLite driver + RelevanceRankingService without CI bootstrap issues
$dbFile = __DIR__ . '/../writable/database.db';
if (!file_exists($dbFile)) { fwrite(STDERR, "no db at $dbFile\n"); exit(1); }
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require __DIR__ . '/../app/Services/RelevanceRankingService.php';
use App\Services\RelevanceRankingService;
$svc = new RelevanceRankingService();

function fetch(string $kw, PDO $pdo, int $limit=5): array {
    // Emulate ArsipModel::buildSearchQuery (SQLite date expr)
    $like = '%' . $kw . '%';
    $sql = "SELECT a.*, k.retensi, date(a.tanggal, '+' || k.retensi || ' years') as b,
            (CASE WHEN date(a.tanggal, '+' || k.retensi || ' years') < date('now') THEN 'sudah' ELSE 'belum' END) as f,
            k.kode as nama_kode, l.nama_lokasi, m.nama_media, p.nama_pencipta, pn.nama_pengolah
            FROM data_arsip a
            JOIN master_kode k ON k.id = a.kode
            JOIN master_lokasi l ON l.id = a.lokasi
            JOIN master_media m ON m.id = a.media
            JOIN master_pencipta p ON p.id = a.pencipta
            JOIN master_pengolah pn ON pn.id = a.unit_pengolah
            WHERE a.deleted_at IS NULL AND (a.noarsip LIKE :kw OR a.uraian LIKE :kw OR a.nobox LIKE :kw)
            LIMIT $limit";
    $st = $pdo->prepare($sql);
    $st->execute([':kw'=>$like]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function out($label, $rows, $max=5) {
    echo "\n== $label (" . count($rows) . " rows) ==\n";
    foreach (array_slice($rows, 0, $max) as $r) {
        $score = isset($r['score']) ? sprintf("%.4f",$r['score']) : '-';
        $rel = isset($r['score_rel']) ? sprintf("%.2f",$r['score_rel']) : '-';
        $time = isset($r['score_time']) ? sprintf("%.2f",$r['score_time']) : '-';
        $relasi = isset($r['score_relasi']) ? sprintf("%.2f",$r['score_relasi']) : '-';
        $b = $r['b'] ?? '-'; $f=$r['f'] ?? '-';
        echo sprintf("  id=%-4s score=%-6s rel=%-5s time=%-5s relasi=%-5s b=%-10s f=%-5s | %s | %.65s\n",
            $r['id'], $score, $rel, $time, $relasi, $b, $f, $r['noarsip'], $r['uraian']);
    }
}

$tests = ['rekrutmen', 'anggaran', 'cuti pegawai', 'SDM.01', 'pengawasan internal'];
foreach ($tests as $kw) {
    $base = fetch($kw, $pdo, 8);
    // ranked via service
    $map = $svc->buildCooccurrenceMap($base);
    $ranked = $svc->rank($base, $kw, [], $map);
    out("baseline kw='$kw'", $base, 5);
    out("ranked   kw='$kw'", $ranked, 5);
    if ($base && $ranked) {
        $sameOrder = implode(',', array_column($base,'id')) === implode(',', array_column($ranked,'id'));
        echo "  order changed? " . ($sameOrder ? "NO (same)" : "YES — ranking reorders") . "\n";
        echo "  b/f sample baseline: b={$base[0]['b']} f={$base[0]['f']} | ranked top b={$ranked[0]['b']} f={$ranked[0]['f']}\n";
    }
}
echo "\nOK — PDO smoke passed\n";
