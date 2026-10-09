<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * LargeScaleSeeder — 2000+ rows for pagination & performance testing.
 *
 * Uses deterministic Faker-like generation (mt_srand) so results are reproducible.
 * Covers all retensi buckets (1/3/5/10) and all kode/pencipta combos for
 * meaningful relations statistics.
 */
class LargeScaleSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;
        $kodeRows = $db->table('master_kode')->get()->getResultArray();
        $penciptaRows = $db->table('master_pencipta')->get()->getResultArray();
        $pengolahRows = $db->table('master_pengolah')->get()->getResultArray();
        $lokasiRows = $db->table('master_lokasi')->get()->getResultArray();
        $mediaRows = $db->table('master_media')->get()->getResultArray();

        if ($kodeRows === [] || $penciptaRows === []) {
            echo "LargeScaleSeeder: master tables empty, run ArteriSeeder first\n";
            return;
        }

        $existingCount = (int) $db->table('data_arsip')->countAllResults();
        $target = 2000;
        $toCreate = max(0, $target - $existingCount);
        if ($toCreate === 0) {
            echo "LargeScaleSeeder: already $existingCount rows (>= $target), skipping\n";
            return;
        }

        mt_srand(20260827 + $existingCount);
        $topics = [
            'rekrutmen pegawai', 'mutasi pegawai', 'cuti pegawai', 'disiplin pegawai',
            'anggaran pegawai', 'anggaran umum', 'pengadaan barang', 'pengadaan jasa',
            'pemeliharaan gedung', 'pemeliharaan kendaraan', 'surat tugas', 'perjalanan dinas',
            'laporan keuangan', 'laporan kinerja', 'audit internal', 'pengawasan internal',
            'peraturan kepegawaian', 'hukum kepegawaian', 'arsip vital', 'arsip aktif',
            'pemusnahan arsip', 'penyusutan arsip', 'jadwal retensi', 'klasifikasi arsip',
        ];
        $templates = [
            'Laporan {topic} tahun {year} — {kode_label}',
            'Surat {topic} periode {year} — {pencipta}',
            'Berita acara {topic} {n}/{year} — {pengolah}',
            'Keputusan {topic} {n}/{year}',
            'Notulen rapat {topic} {n}/{year} — {lokasi}',
            'Daftar {topic} {n}/{year}',
            'Rekap {topic} triwulan {n} {year}',
        ];

        $batch = [];
        $batchSize = 500;
        $total = 0;

        for ($i = 0; $i < $toCreate; $i++) {
            $kodeRow = $kodeRows[mt_rand(0, count($kodeRows) - 1)];
            $kodeId = $kodeRow['id'];
            $kodeLabel = $kodeRow['kode'] ?? '000';
            $year = mt_rand(2015, 2026);
            $month = mt_rand(1, 12);
            $day = mt_rand(1, 28);
            $tanggal = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $dateStr = $tanggal;

            $tplIdx = mt_rand(0, count($templates) - 1);
            $topic = $topics[mt_rand(0, count($topics) - 1)];
            $pencId = $penciptaRows[mt_rand(0, count($penciptaRows) - 1)]['id'];
            $pengId = $pengolahRows[mt_rand(0, count($pengolahRows) - 1)]['id'];
            $lokId = $lokasiRows[mt_rand(0, count($lokasiRows) - 1)]['id'];
            $medId = $mediaRows[mt_rand(0, count($mediaRows) - 1)]['id'];

            // Resolve labels for template
            $penciptaLabel = 'Pencipta';
            foreach ($penciptaRows as $pr) {
                if ((int) $pr['id'] === (int) $pencId) { $penciptaLabel = $pr['nama_pencipta']; break; }
            }
            $pengolahLabel = 'Unit';
            foreach ($pengolahRows as $pr) {
                if ((int) $pr['id'] === (int) $pengId) { $pengolahLabel = $pr['nama_pengolah']; break; }
            }
            $lokasiLabel = 'Gedung A';
            foreach ($lokasiRows as $lr) {
                if ((int) $lr['id'] === (int) $lokId) { $lokasiLabel = $lr['nama_lokasi']; break; }
            }

            $n = $existingCount + $i + 1;
            $uraian = strtr($templates[$tplIdx], [
                '{kode_label}' => $kodeLabel,
                '{year}'       => (string) $year,
                '{n}'          => (string) $n,
                '{topic}'      => $topic,
                '{pencipta}'   => $penciptaLabel,
                '{pengolah}'   => $pengolahLabel,
                '{lokasi}'     => $lokasiLabel,
                '{date}'       => $dateStr,
            ]);
            if (mt_rand(1, 10) <= 2) {
                $uraian .= ' — ' . $topic;
            }

            $batch[] = [
                'noarsip'       => sprintf('ARS/%04d/%02d', $n, $year % 100),
                'pencipta'      => (string) $pencId,
                'unit_pengolah' => (string) $pengId,
                'tanggal'       => $tanggal,
                'uraian'        => $uraian,
                'ket'           => mt_rand(0, 1) ? 'asli' : 'copy',
                'kode'          => (string) $kodeId,
                'jumlah'        => mt_rand(1, 10),
                'nobox'         => sprintf('BOX-%03d', mt_rand(1, 80)),
                'lokasi'        => (string) $lokId,
                'media'         => (string) $medId,
                'file'          => '',
                'username'      => 'admin',
                'tgl_input'     => date('Y-m-d H:i:s'),
                'tgl_update'    => date('Y-m-d H:i:s'),
            ];

            if (count($batch) >= $batchSize) {
                $db->table('data_arsip')->insertBatch($batch);
                $total += count($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $db->table('data_arsip')->insertBatch($batch);
            $total += count($batch);
        }

        // Refresh stat
        try {
            $db->query('DELETE FROM stat_kode_pencipta');
        } catch (\Throwable $e) {}
        try {
            $db->query("INSERT INTO stat_kode_pencipta (kode, pencipta, cnt) SELECT kode, pencipta, COUNT(*) FROM data_arsip WHERE deleted_at IS NULL GROUP BY kode, pencipta");
            $cnt = (int) $db->table('stat_kode_pencipta')->countAllResults();
            echo "LargeScaleSeeder: inserted $total rows, stat pairs $cnt, total data_arsip " . (int) $db->table('data_arsip')->countAllResults() . "\n";
        } catch (\Throwable $e) {
            echo "LargeScaleSeeder: inserted $total rows, stat refresh skipped: " . $e->getMessage() . "\n";
        }
    }
}
