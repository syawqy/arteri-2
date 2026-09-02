<?php

declare(strict_types=1);

/**
 * Script Evaluasi Benchmark Empiris: Records in Contexts (RiC) Discovery vs Baseline Relasional
 * 
 * Menguji efektivitas penemuan berkas terkait (dossier discovery & context network)
 * menggunakan metrik:
 * 1. Precision@K (P@5, P@10): Ketepatan berkas terkait yang relevan secara provenans fungsional
 * 2. Cluster Coverage (Recall@K): Cakupan berkas dalam satu klaster urusan/proses bisnis
 * 3. Mean Reciprocal Rank (MRR): Kecepatan penemuan berkas terdekat pertama
 */

require_once __DIR__ . '/../app/Services/RicContextualDiscoveryService.php';

use App\Services\RicContextualDiscoveryService;

// Buat dataset sintetis kearsipan multi-klaster untuk pengujian terkontrol (Ground Truth)
// Setiap klaster mewakili suatu proses bisnis nyata instansi
$clusters = [
    'KLAS-01' => [
        'name' => 'Pengadaan Server dan Infrastruktur TI 2025',
        'pencipta' => 'Pusat Data dan Informasi',
        'unit_pengolah' => 'Bidang Infrastruktur',
        'kode' => 'TI.01.01',
        'items' => [
            ['id' => 1, 'noarsip' => 'TI/2025/001', 'uraian' => 'Surat Keputusan Penetapan PPK Pengadaan Server TI 2025', 'tanggal' => '2025-01-10'],
            ['id' => 2, 'noarsip' => 'TI/2025/002', 'uraian' => 'Kerangka Acuan Kerja (KAK) Pengadaan Server Komputasi', 'tanggal' => '2025-01-15'],
            ['id' => 3, 'noarsip' => 'TI/2025/003', 'uraian' => 'Harga Perkiraan Sendiri (HPS) Pengadaan Perangkat Jaringan', 'tanggal' => '2025-01-20'],
            ['id' => 4, 'noarsip' => 'TI/2025/004', 'uraian' => 'Berita Acara Evaluasi Penawaran Lelang Server', 'tanggal' => '2025-02-05'],
            ['id' => 5, 'noarsip' => 'TI/2025/005', 'uraian' => 'Surat Perjanjian Kontrak Pengadaan Server Data Center', 'tanggal' => '2025-02-20'],
            ['id' => 6, 'noarsip' => 'TI/2025/006', 'uraian' => 'Berita Acara Serah Terima Hasil Pekerjaan Server TI', 'tanggal' => '2025-04-10'],
            ['id' => 7, 'noarsip' => 'TI/2025/007', 'uraian' => 'Kuitansi Pembayaran Termin 100% Pengadaan Server', 'tanggal' => '2025-04-25'],
            ['id' => 8, 'noarsip' => 'TI/2025/008', 'uraian' => 'Laporan Pengawasan dan Uji Fungsi Server', 'tanggal' => '2025-04-30'],
        ]
    ],
    'KLAS-02' => [
        'name' => 'Rekrutmen Calon Pegawai Negeri Sipil Formasi 2024',
        'pencipta' => 'Biro Kepegawaian dan Organisasi',
        'unit_pengolah' => 'Bagian Pengadaan Pegawai',
        'kode' => 'KP.02.01',
        'items' => [
            ['id' => 9, 'noarsip' => 'KP/2024/001', 'uraian' => 'Pengumuman Penerimaan CPNS Tahun Anggaran 2024', 'tanggal' => '2024-08-01'],
            ['id' => 10, 'noarsip' => 'KP/2024/002', 'uraian' => 'Daftar Hasil Seleksi Administrasi Berkas Pelamar CPNS', 'tanggal' => '2024-08-25'],
            ['id' => 11, 'noarsip' => 'KP/2024/003', 'uraian' => 'Jadwal Pelaksanaan Seleksi Kompetensi Dasar (SKD)', 'tanggal' => '2024-09-10'],
            ['id' => 12, 'noarsip' => 'KP/2024/004', 'uraian' => 'Rekapitulasi Nilai Seleksi Kompetensi Bidang (SKB)', 'tanggal' => '2024-10-15'],
            ['id' => 13, 'noarsip' => 'KP/2024/005', 'uraian' => 'Surat Penetapan Kelulusan Akhir Peserta CPNS 2024', 'tanggal' => '2024-11-01'],
            ['id' => 14, 'noarsip' => 'KP/2024/006', 'uraian' => 'Usulan Penetapan Nomor Induk Pegawai (NIP) ke BKN', 'tanggal' => '2024-11-20'],
        ]
    ],
    'KLAS-03' => [
        'name' => 'Audit Keuangan Semester II Tahun 2024',
        'pencipta' => 'Inspektorat Jenderal',
        'unit_pengolah' => 'Inspektorat Wilayah I',
        'kode' => 'PW.01.02',
        'items' => [
            ['id' => 15, 'noarsip' => 'PW/2024/001', 'uraian' => 'Surat Tugas Pelaksanaan Audit Kinerja dan Keuangan Semester II', 'tanggal' => '2024-07-05'],
            ['id' => 16, 'noarsip' => 'PW/2024/002', 'uraian' => 'Kertas Kerja Audit Realisasi Anggaran Belanja Modal', 'tanggal' => '2024-07-25'],
            ['id' => 17, 'noarsip' => 'PW/2024/003', 'uraian' => 'Daftar Temuan Sementara Hasil Pemeriksaan Keuangan', 'tanggal' => '2024-08-10'],
            ['id' => 18, 'noarsip' => 'PW/2024/004', 'uraian' => 'Tanggapan Auditi atas Temuan Pemeriksaan Inspektorat', 'tanggal' => '2024-08-20'],
            ['id' => 19, 'noarsip' => 'PW/2024/005', 'uraian' => 'Laporan Hasil Pemeriksaan (LHP) Keuangan Semester II', 'tanggal' => '2024-09-05'],
        ]
    ],
    'KLAS-04' => [
        'name' => 'Penyusunan Rencana Kerja dan Anggaran (RKA-K/L) 2026',
        'pencipta' => 'Biro Perencanaan dan Anggaran',
        'unit_pengolah' => 'Bagian Penyusunan Anggaran',
        'kode' => 'PR.01.01',
        'items' => [
            ['id' => 20, 'noarsip' => 'PR/2025/001', 'uraian' => 'Surat Edaran Penyusunan Pagu Indikatif Tahun Anggaran 2026', 'tanggal' => '2025-03-01'],
            ['id' => 21, 'noarsip' => 'PR/2025/002', 'uraian' => 'Matriks Usulan Rencana Kerja Unit Eselon I Tahun 2026', 'tanggal' => '2025-03-20'],
            ['id' => 22, 'noarsip' => 'PR/2025/003', 'uraian' => 'Notula Rapat Koordinasi Penelaahan Alokasi Anggaran Belanja', 'tanggal' => '2025-04-05'],
            ['id' => 23, 'noarsip' => 'PR/2025/004', 'uraian' => 'Dokumen Rancangan RKA-K/L Kementerian Tahun 2026', 'tanggal' => '2025-05-15'],
        ]
    ]
];

// Flat-kan semua item ke dalam pool repositori data arsip
$allRecords = [];
$groundTruthClusterMap = [];

foreach ($clusters as $cId => $cData) {
    foreach ($cData['items'] as $item) {
        $record = array_merge($item, [
            'pencipta' => $cData['pencipta'],
            'unit_pengolah' => $cData['unit_pengolah'],
            'kode' => $cData['kode'],
            'cluster_id' => $cId
        ]);
        $allRecords[] = $record;
        $groundTruthClusterMap[$record['id']] = $cId;
    }
}

// Tambahkan "Noise Records" (arsip acak dari unit lain agar simulasi realistis)
$noiseAgents = ['Biro Hukum', 'Pusat Kerja Sama Internasional', 'Biro Umum', 'Puslitbang Transportasi'];
$noiseKodes = ['HK.01.01', 'KS.02.01', 'UM.01.02', 'LT.03.01'];
for ($i = 1; $i <= 30; $i++) {
    $nId = 100 + $i;
    $record = [
        'id' => $nId,
        'noarsip' => "NOISE/2025/{$i}",
        'uraian' => "Dokumen Surat Perjalanan Dinas dan Administrasi Rutin Nomor {$i}",
        'tanggal' => date('Y-m-d', strtotime("2024-01-01 + " . ($i * 10) . " days")),
        'pencipta' => $noiseAgents[$i % count($noiseAgents)],
        'unit_pengolah' => 'Bagian Tata Usaha',
        'kode' => $noiseKodes[$i % count($noiseKodes)],
        'cluster_id' => 'NOISE'
    ];
    $allRecords[] = $record;
    $groundTruthClusterMap[$nId] = 'NOISE';
}

$ricService = new RicContextualDiscoveryService();

// Struktur Evaluasi
$results = [
    'ric' => ['p5' => [], 'p10' => [], 'recall' => [], 'mrr' => []],
    'baseline_chronological' => ['p5' => [], 'p10' => [], 'recall' => [], 'mrr' => []],
    'baseline_random' => ['p5' => [], 'p10' => [], 'recall' => [], 'mrr' => []],
];

// Jalankan pengujian penelusuran (Discovery Test) untuk setiap berkas sebagai seed
$testSeeds = array_filter($allRecords, fn($r) => $r['cluster_id'] !== 'NOISE');

foreach ($testSeeds as $seed) {
    $seedId = $seed['id'];
    $targetCluster = $seed['cluster_id'];
    
    // Hitung total berkas relevan dalam klaster yang sama (selain dirinya sendiri)
    $relevantInCluster = array_filter($allRecords, fn($r) => $r['cluster_id'] === $targetCluster && $r['id'] !== $seedId);
    $totalRelevant = count($relevantInCluster);
    if ($totalRelevant === 0) continue;

    // 1. Metode A: RiC Contextual Affinity Scoring
    $scoredCandidates = [];
    foreach ($allRecords as $candidate) {
        if ($candidate['id'] === $seedId) continue;
        $scores = $ricService->computeAffinity($seed, $candidate);
        $candidate['cas_score'] = $scores['cas_score'];
        $scoredCandidates[] = $candidate;
    }
    usort($scoredCandidates, fn($a, $b) => $b['cas_score'] <=> $a['cas_score']);

    // 2. Metode B: Baseline Kronologis (Sistem Konvensional tanpa RiC: ORDER BY tanggal DESC)
    $chronoCandidates = array_filter($allRecords, fn($r) => $r['id'] !== $seedId);
    usort($chronoCandidates, fn($a, $b) => strcmp($b['tanggal'], $a['tanggal']));

    // Evaluasi Metrik
    foreach (['ric' => $scoredCandidates, 'baseline_chronological' => $chronoCandidates] as $method => $rankedList) {
        $top5 = array_slice($rankedList, 0, 5);
        $top10 = array_slice($rankedList, 0, 10);

        // Precision@5
        $relCount5 = count(array_filter($top5, fn($r) => $r['cluster_id'] === $targetCluster));
        $p5 = $relCount5 / 5.0;

        // Precision@10
        $relCount10 = count(array_filter($top10, fn($r) => $r['cluster_id'] === $targetCluster));
        $p10 = $relCount10 / 10.0;

        // Recall (Cluster Coverage @ 10)
        $recall10 = min(1.0, $relCount10 / $totalRelevant);

        // Mean Reciprocal Rank (MRR)
        $firstRank = 0;
        foreach ($rankedList as $rank => $item) {
            if ($item['cluster_id'] === $targetCluster) {
                $firstRank = $rank + 1;
                break;
            }
        }
        $rr = ($firstRank > 0) ? (1.0 / $firstRank) : 0.0;

        $results[$method]['p5'][] = $p5;
        $results[$method]['p10'][] = $p10;
        $results[$method]['recall'][] = $recall10;
        $results[$method]['mrr'][] = $rr;
    }
}

// Hitung Agregat Rata-rata
$avg = [];
foreach (['ric', 'baseline_chronological'] as $method) {
    $count = count($results[$method]['p5']);
    $avg[$method] = [
        'P@5' => array_sum($results[$method]['p5']) / $count,
        'P@10' => array_sum($results[$method]['p10']) / $count,
        'Recall@10' => array_sum($results[$method]['recall']) / $count,
        'MRR' => array_sum($results[$method]['mrr']) / $count,
    ];
}

// Format Output Tabel
echo "========================================================================================\n";
echo "           HASIL EVALUASI BENCHMARK: RECORDS IN CONTEXTS (RiC) DISCOVERY                \n";
echo "========================================================================================\n";
echo sprintf("%-28s | %-10s | %-10s | %-12s | %-10s\n", "Metode Penelusuran", "P@5", "P@10", "Recall@10", "MRR");
echo "----------------------------------------------------------------------------------------\n";
echo sprintf("%-28s | %-10.4f | %-10.4f | %-12.4f | %-10.4f\n", "Baseline (Kronologis)", $avg['baseline_chronological']['P@5'], $avg['baseline_chronological']['P@10'], $avg['baseline_chronological']['Recall@10'], $avg['baseline_chronological']['MRR']);
echo sprintf("%-28s | %-10.4f | %-10.4f | %-12.4f | %-10.4f\n", "RiC Contextual Discovery", $avg['ric']['P@5'], $avg['ric']['P@10'], $avg['ric']['Recall@10'], $avg['ric']['MRR']);
echo "----------------------------------------------------------------------------------------\n";

$deltaP5 = $avg['ric']['P@5'] - $avg['baseline_chronological']['P@5'];
$deltaP10 = $avg['ric']['P@10'] - $avg['baseline_chronological']['P@10'];
$deltaRecall = $avg['ric']['Recall@10'] - $avg['baseline_chronological']['Recall@10'];
$deltaMRR = $avg['ric']['MRR'] - $avg['baseline_chronological']['MRR'];

echo sprintf("%-28s | %+10.4f | %+10.4f | %+12.4f | %+10.4f\n", "Peningkatan (Delta)", $deltaP5, $deltaP10, $deltaRecall, $deltaMRR);
echo "========================================================================================\n";

// Simpan ringkasan hasil ke file JSON/Markdown untuk disertakan di naskah jurnal
$summaryFile = __DIR__ . '/../docs/ric-discovery/BENCHMARK-RESULTS.json';
file_put_contents($summaryFile, json_encode([
    'timestamp' => date('c'),
    'total_queries' => count($testSeeds),
    'metrics' => $avg,
    'delta' => [
        'P@5' => $deltaP5,
        'P@10' => $deltaP10,
        'Recall@10' => $deltaRecall,
        'MRR' => $deltaMRR,
    ]
], JSON_PRETTY_PRINT));
echo "\nHasil benchmark lengkap berhasil disimpan ke: docs/ric-discovery/BENCHMARK-RESULTS.json\n";
