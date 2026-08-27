<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * SaracevicRankingSeeder
 *
 * Seeds ~120 archive records with controlled variation for evaluating
 * Saracevic relevance ranking (Fafalios 1810.11049 adapted):
 *  - 18 master_kode (retensi 1/3/5/10) × 6 lokasi × 10 media × 9 pencipta/pengolah
 *  - tanggal spread: 2018..2026 to create varied expiry (b = tanggal + retensi)
 *  - uraian crafted to test relativeness (keyword overlap) + timeliness + relations
 *
 * Idempotent: truncates are NOT used; inserts ignore duplicates only for masters
 * (masters already seeded by ArteriSeeder). Data rows are inserted fresh each run
 * — use `php spark db:seed SaracevicRankingSeeder` after ArteriSeeder.
 */
class SaracevicRankingSeeder extends Seeder
{
    public function run(): void
    {
        $db = $this->db;

        // Ensure masters exist (fetch IDs)
        $kodeRows     = $db->table('master_kode')->orderBy('id', 'ASC')->get()->getResultArray();
        $penciptaRows = $db->table('master_pencipta')->orderBy('id', 'ASC')->get()->getResultArray();
        $pengolahRows = $db->table('master_pengolah')->orderBy('id', 'ASC')->get()->getResultArray();
        $lokasiRows   = $db->table('master_lokasi')->orderBy('id', 'ASC')->get()->getResultArray();
        $mediaRows    = $db->table('master_media')->orderBy('id', 'ASC')->get()->getResultArray();

        if ($kodeRows === [] || $penciptaRows === []) {
            echo "Masters empty — run ArteriSeeder first.\n";
            return;
        }

        $kodeIds     = array_column($kodeRows, 'id');
        $pencIds     = array_column($penciptaRows, 'id');
        $pengIds     = array_column($pengolahRows, 'id');
        $lokIds      = array_column($lokasiRows, 'id');
        $medIds      = array_column($mediaRows, 'id');

        // Uraian templates — 30 base phrases, varied length & keyword density
        $templates = [
            'Laporan {kode_label} tentang rekrutmen pegawai tahun {year} untuk unit {pengolah}',
            'Berita acara {kode_label} mutasi pegawai — SK Direktur No. {n}',
            'Dokumen pengembangan pegawai: pelatihan {topic} angkatan {n}',
            'Surat cuti pegawai {kode_label} periode {year} — bidang {pencipta}',
            'Rencana anggaran {kode_label} KEU tahun {year} untuk {topic}',
            'Realisasi anggaran pegawai triwulan {n} {year}',
            'Realisasi anggaran umum dan rumah tangga semester {n} {year}',
            'Peraturan perusahaan {kode_label}: pedoman {topic} tahun {year}',
            'Keputusan direksi {kode_label} tentang {topic} — rapat {n}',
            'Peraturan direksi {kode_label} mengenai {topic}',
            'Laporan pengawasan internal {kode_label} audit {year} unit {pengolah}',
            'Penelitian dan pengembangan {topic} — laporan akhir {year}',
            'Inventarisasi barang bergerak {kode_label} gedung {lokasi}',
            'Inventarisasi barang tidak bergerak {kode_label} aset {year}',
            'Evaluasi kinerja pegawai {kode_label} bidang {pencipta} {year}',
            'Sertifikat pelatihan {topic} peserta {n} tahun {year}',
            'Kontrak pengadaan {topic} No. {n}/{year}',
            'Notulen rapat {kode_label} {topic} tanggal {date}',
            'Surat tugas {kode_label} perjalanan dinas {topic}',
            'Laporan keuangan {kode_label} audited {year}',
            'Dokumen mutasi jabatan {kode_label} {pencipta} ke {pengolah}',
            'Arsip cuti bersama {year} — rekap {kode_label}',
            'Pengembangan sistem informasi {topic} {kode_label}',
            'Beasiswa pegawai program {topic} tahun {year}',
            'Pengangkatan pegawai {kode_label} CPNS {year} formasi {n}',
            'Pemberhentian pegawai {kode_label} pensiun {year}',
            'RND riset {topic} — proposal {kode_label} {year}',
            'Umum inventaris {kode_label} penghapusan aset {year}',
            'Hukum pengawasan {kode_label} temuan audit {n}/{year}',
            'SDM rekrutmen {kode_label} seleksi administrasi {year}',
        ];

        $topics = ['kepegawaian','anggaran','hukum','teknologi informasi','logistik','pengadaan','SDM','audit','keuangan','arsip digital','mutasi','pelatihan','riset'];
        $count = 120;
        $rows = [];
        $existingCount = (int) $db->table('data_arsip')->countAllResults();

        // Deterministic seed for reproducibility
        mt_srand(20260827 + $existingCount);

        for ($i = 0; $i < $count; $i++) {
            $tplIdx = $i % count($templates);
            $topic  = $topics[array_rand($topics)];
            $kodeId = $kodeIds[array_rand($kodeIds)];
            $pencId = $pencIds[array_rand($pencIds)];
            $pengId = $pengIds[array_rand($pengIds)];
            $lokId  = $lokIds[array_rand($lokIds)];
            $medId  = $medIds[array_rand($medIds)];

            // Year spread 2018..2026 to create varied timeliness
            $year = mt_rand(2018, 2026);
            $month = str_pad((string) mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
            $day = str_pad((string) mt_rand(1, 28), 2, '0', STR_PAD_LEFT);
            $tanggal = sprintf('%d-%s-%s', $year, $month, $day);
            $dateStr = $tanggal;

            // Find kode label for template
            $kodeLabel = 'SDM.01';
            foreach ($kodeRows as $kr) {
                if ((int) $kr['id'] === (int) $kodeId) { $kodeLabel = $kr['kode']; break; }
            }
            $penciptaLabel = 'Bidang';
            foreach ($penciptaRows as $pr) {
                if ((int) $pr['id'] === (int) $pencId) { $penciptaLabel = $pr['nama_pencipta']; break; }
            }
            $pengolahLabel = 'Unit Arsip';
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

            // Inject keyword density variation: some rows repeat topic
            if (mt_rand(1, 10) <= 3) {
                $uraian .= ' — ' . $topic . ' ' . $topic;
            }

            $rows[] = [
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
        }

        $db->table('data_arsip')->insertBatch($rows);
        echo "Seeded " . count($rows) . " archive rows for Saracevic ranking evaluation.\n";

        // Refresh stat_kode_pencipta (hybrid SQL ranking)
        try {
            $db->query('DELETE FROM stat_kode_pencipta');
        } catch (\Throwable $e) {
        }
        try {
            $db->query("INSERT INTO stat_kode_pencipta (kode, pencipta, cnt) SELECT kode, pencipta, COUNT(*) as cnt FROM data_arsip WHERE deleted_at IS NULL GROUP BY kode, pencipta");
            $cnt = (int) $db->table('stat_kode_pencipta')->countAllResults();
            echo "Refreshed stat_kode_pencipta: $cnt pairs\n";
        } catch (\Throwable $e) {
            // table may not exist yet (migration not run) — skip
            echo "stat_kode_pencipta not available (run migration)\n";
        }
    }
}
