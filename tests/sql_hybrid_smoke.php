#!/usr/bin/env php
<?php
// Test SQL hybrid ranking via PDO — proves pagination consistency & driver correctness
$dbFile = __DIR__ . '/../writable/database.db';
if (!file_exists($dbFile)) { fwrite(STDERR, "no db\n"); exit(1); }
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== SQL Hybrid Smoke (driver=SQLite) ===\n";
echo "total arsip: " . $pdo->query("SELECT count(*) FROM data_arsip")->fetchColumn() . "\n";
echo "stat pairs: " . $pdo->query("SELECT count(*) FROM stat_kode_pencipta")->fetchColumn() . "\n\n";

// Helper: emulate ArsipModel::searchRanked SQL (SQLite branch) for one keyword
function sqlRanked(PDO $pdo, string $kw, int $limit, int $offset): array {
    $tok = trim(mb_strtolower($kw, 'UTF-8'));
    $tokens = $tok !== '' ? array_values(array_unique(array_filter(preg_split('/\s+/u', $tok)))) : [];
    $n = count($tokens);
    if ($n === 0) {
        $relExpr = '0';
    } else {
        $per=[]; foreach($tokens as $t){
            $t = str_replace("'", "''", $t);
            $uraian = "(CASE WHEN LOWER(a.uraian) LIKE '% $t %' OR LOWER(a.uraian) LIKE '$t %' OR LOWER(a.uraian) LIKE '% $t' OR LOWER(a.uraian) = '$t' THEN 3 WHEN LOWER(a.uraian) LIKE '%$t%' THEN 1.5 ELSE 0 END)";
            $noars = "(CASE WHEN LOWER(a.noarsip) LIKE '%$t%' THEN 2 ELSE 0 END)";
            $nobox = "(CASE WHEN LOWER(a.nobox) LIKE '%$t%' THEN 1 ELSE 0 END)";
            $penc = "(CASE WHEN LOWER(p.nama_pencipta) LIKE '%$t%' THEN 1.5 ELSE 0 END)";
            $peng = "(CASE WHEN LOWER(pn.nama_pengolah) LIKE '%$t%' THEN 1.5 ELSE 0 END)";
            $kode = "(CASE WHEN LOWER(k.kode) LIKE '%$t%' OR LOWER(k.nama) LIKE '%$t%' THEN 1.5 ELSE 0 END)";
            $per[] = "max(max(max(max(max($uraian,$noars),$nobox),$penc),$peng),$kode)";
        }
        $relExpr = "min(1.0, ((" . implode(' + ', $per) . ") / " . ($n*3.0) . "))";
    }
    $expiry = "date(a.tanggal, '+' || k.retensi || ' years')";
    $base = "(1.0 / (1.0 + (ABS(julianday($expiry) - julianday('now')))/365.0))";
    $boost = "(CASE WHEN $expiry < date('now') THEN 0.08 ELSE 0 END)";
    $timeExpr = "COALESCE(min(1.0, $base + $boost), 0.5)";
    $relasiExpr = "COALESCE(CAST(s.cnt AS REAL) / NULLIF(s_max.max_cnt, 0), 0)";
    $scoreExpr = "(0.5*($relExpr) + 0.3*($timeExpr) + 0.2*($relasiExpr))";
    $like = '%' . $kw . '%';
    $sql = "SELECT a.*, k.retensi, date(a.tanggal, '+' || k.retensi || ' years') as b,
        (CASE WHEN date(a.tanggal, '+' || k.retensi || ' years') < date('now') THEN 'sudah' ELSE 'belum' END) as f,
        k.kode as nama_kode, ($relExpr) as score_rel, ($timeExpr) as score_time, ($relasiExpr) as score_relasi, ($scoreExpr) as score
        FROM data_arsip a
        JOIN master_kode k ON k.id=a.kode
        JOIN master_lokasi l ON l.id=a.lokasi
        JOIN master_media m ON m.id=a.media
        JOIN master_pencipta p ON p.id=a.pencipta
        JOIN master_pengolah pn ON pn.id=a.unit_pengolah
        LEFT JOIN stat_kode_pencipta s ON s.kode=a.kode AND s.pencipta=a.pencipta
        CROSS JOIN (SELECT MAX(cnt) as max_cnt FROM stat_kode_pencipta) s_max
        WHERE a.deleted_at IS NULL AND (a.noarsip LIKE :kw OR a.uraian LIKE :kw OR a.nobox LIKE :kw)
        ORDER BY score DESC, a.tanggal DESC, a.id DESC LIMIT $limit OFFSET $offset";
    $st=$pdo->prepare($sql); $st->execute([':kw'=>$like]); return $st->fetchAll(PDO::FETCH_ASSOC);
}

$tests = ['rekrutmen','anggaran','surat tugas','arsip','laporan'];
foreach($tests as $kw){
    $p1 = sqlRanked($pdo,$kw,5,0);
    $p2 = sqlRanked($pdo,$kw,5,5);
    $p3 = sqlRanked($pdo,$kw,5,10);
    echo "kw='$kw'  p1 ids=".implode(',',array_column($p1,'id'))."  scores=".implode(',',array_map(fn($r)=>number_format((float)$r['score'],3),$p1))."\n";
    echo "         p2 ids=".implode(',',array_column($p2,'id'))."  p3 ids=".implode(',',array_column($p3,'id'))."\n";
    // Check no overlap and descending score across pages
    $all = array_merge($p1,$p2,$p3);
    $ids = array_column($all,'id');
    $dup = count($ids) !== count(array_unique($ids)) ? 'DUP!' : 'no dup';
    $scores = array_map(fn($r)=>(float)$r['score'],$all);
    $sorted = $scores; rsort($sorted);
    $orderOk = $scores === $sorted ? 'order OK' : 'ORDER BROKEN';
    echo "         $dup  $orderOk  (".count($all)." rows)\n";
    // Show sample score breakdown
    if($p1){ $r=$p1[0]; echo "         top: uraian=".substr($r['uraian'],0,55)."  rel=".number_format((float)$r['score_rel'],2)." time=".number_format((float)$r['score_time'],2)." relasi=".number_format((float)$r['score_relasi'],2)." b=".$r['b']." f=".$r['f']."\n"; }
    echo "\n";
}

// Benchmark 120 vs 2000 later
echo "OK — SQL hybrid pagination smoke done\n";
