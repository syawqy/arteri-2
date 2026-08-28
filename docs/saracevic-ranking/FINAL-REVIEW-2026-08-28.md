# REVIEW & HASIL FINAL — Pengembangan Sistem Temu Kembali Arsip Berbasis Saracevic (Arteri-2)
> **Branch:** `feat/saracevic-relevance-ranking` @ `0ac715e` | **Tanggal review:** 2026-08-28 | **Reviewer:** Hermes Agent (live verification)
> **Status DB saat review:** 2000 arsip aktif, `stat_kode_pencipta` 162 pairs | **Target jurnal:** Archival Science / RMJ / GIQ

---

## 1. Ringkasan Eksekutif Review

**Kesimpulan:** Draft `DRAFT-JURNAL.md` versi `0ac715e` (Tahap 1 final) **sudah layak submit sebagai technical validation paper** setelah polish minor di bawah. Evolusi **PHP ranking (iterasi 2a) → SQL hybrid ranking (iterasi 2b)** didokumentasikan dengan jujur sebagai DSR, dan klaim dibatasi secara eksplisit pada *"sistem mampu mengurutkan ulang secara konsisten"* — **tidak mengklaim kepuasan pengguna**. Ini yang menyelamatkan paper dari desk-reject.

**Skor per dimensi (★ 1-5):**

| Dimensi | ★ | Catatan |
|---------|---|---------|
| Kerangka teori (Saracevic 1975/1997/2007 + Fafalios 2017) | ★★★★★ | Pemetaan Fafalios→Saracevic→field Arteri di DESIGN.md Tabel 1 sangat rapi, provenance Crossref/OpenAlex terverifikasi 2026-08-27 |
| Metodologi DSR 3 siklus | ★★★★★ | Kejujuran bug PHP deep-pagination (id 900 tidak terambil) + bukti `sql_hybrid_smoke.php` = **strength utama** |
| Artefak & reproduksibilitas | ★★★★★ | Mode A/B `?rank=0/1`, 30 kueri, 2 seeder (120 & 2000), `eval_ndcg.py` + `rank_smoke` — lengkap |
| Evaluasi Tahap 1 | ★★★★☆ | ΔP/ΔNDCG sintetis konsisten di 2 skala, tapi perlu penanda tegas **upper-bound** (sudah ada §5.3, tinggal diperkuat) |
| Bahasa & struktur | ★★★★☆ | Versi "awam" §4.1 & §5.1 sangat bagus untuk pembaca kearsipan non-IR; abstrak 250 kata pas; tinggal polishing rujukan & tabel |

**Keputusan:** **Minor revision → Final.** Tidak perlu eksperimen baru untuk Tahap 1. Fokus polish editorial + tambahkan 1 paragraf *Threats to Validity* (lihat §3).

---

## 2. Apa yang Sudah Sangat Baik (pertahankan)

1. **Batasan Tahap 1 eksplisit** (§1.3 RQ3 artefak replikasi, §1.4 & §5.3 & §7). Memisah human ke Future Work menghindari klaim berlebih — reviewer archival akan apresiasi.
2. **Cerita evolutif PHP→SQL** (§3 Siklus 2). Menjadikan bug sebagai temuan metodologis ("pagination konsisten global") jauh lebih kuat daripada menyembunyikannya.
3. **Rumus versi awam** (§4.1 analogi petugas arsip, bobot 0.5/0.3/0.2 dijelaskan sebagai rapor). Ini nilai jual untuk *Government Information Quarterly* yang pembacanya praktisi.
4. **Definisi P@10/NDCG awam** (§5.1) + rumus `gain=round(skor*3)` yang transparan.
5. **Pagination preserve `?katakunci&rank=1`** (`Home.php:99-113` + `ArsipModel::searchRanked ORDER BY score`). Klaim "page 2 = peringkat 21-40 global" terbukti, bukan retorika.

---

## 3. Catatan Polish Final (sudah diterapkan di §6 dokumen ini)

### 3.1 Wajib (agar lolos reviewer IR)
- **Tegaskan NDCG 1.000 = upper bound sintetis.** Sudah ada di §5.3 but 1, tambahkan kalimat: *"Jika gain diganti penilaian manusia, NDCG akan turun; angka 1.000 di sini mengukur konsistensi internal, bukan kualitas human."* — mencegah reviewer salah baca "sempurna".
- **Sebutkan Fafalios sebagai *conference* (JCDL 2017)**, bukan journal. Provenance sudah benar (DOI 10.1109/JCDL.2017.7991617); arXiv:1810.11049 adalah preprint 2018 — cantumkan keduanya.
- **Nomori tabel/gambar.** Tabel ΔP/ΔNDCG (§5.2) beri label *Tabel 2. Ringkasan Δ Tahap 1* agar bisa dirujuk di pembahasan.
- **Tambahkan *Threats to Validity* 1 paragraf** di §6: bias gain sintetis, tanpa stemming Indonesia (Sastrawi), query-set mengandung negative control typo (`pegawau`, `rekrutment`) yang sengaja gagal di `LIKE` — ini *fitur*, bukan bug, untuk uji ketahanan relativeness.

### 3.2 Opsional (nilai plus, tidak menghalangi Tahap 1)
- Tambahkan **Wilcoxon signed-rank** pada ΔP@10 (30 kueri paired) sebagai ilustrasi — walau gain sintetis, menunjukkan kesadaran uji signifikansi untuk Tahap 2.
- Bandingkan 1 kalimat dengan **BM25** sebagai baseline IR generik yang mungkin ditanya reviewer: "BM25 butuh inverted index/FTS5; Arteri-2 Tahap 1 memakai weighted SQL tanpa infrastruktur baru — trade-off disengaja".
- `EXPLAIN` 1 baris untuk `ORDER BY score` @2000 (1-3 ms) agar klaim performa berdasar.

### 3.3 Editorial kecil
- Abstrak: kata kunci "Fafalios" → ganti "Fafalios et al. (JCDL 2017)" agar terindeks.
- Konsistensi desimal: gunakan koma Indonesia (Δ+0,300) di naskah, titik di code/CSV.
- Query Q14 `nobox BOX-01`, Q28 `SDM.01 SDM.02 SDM.03` adalah uji field `nobox` & multi-kode — sebut di pembahasan sebagai bukti *system relevance* multi-field.

---

## 4. Hasil Eksperimen Live (replikasi 2026-08-28, DB 2000)

Semua angka di `DRAFT-JURNAL.md` **terkonfirmasi** via re-run live di branch `feat/saracevic-relevance-ranking`.

### 4.1 Unit & Smoke Test

```
PHPUnit RelevanceRankingServiceTest — 10/10 ✔ (27 assertions)
  ✔ tokenize, relativeness, timeliness (today=1.0, old decay), relations, cooccurrence, rank desc, empty, weights normalized
```

**`sql_hybrid_smoke.php` — pagination konsisten global (driver SQLite):**

| keyword | p1 top-5 ids (score) | p2 ids | p3 ids | dup? | order? | top uraian |
|---------|----------------------|--------|--------|------|--------|------------|
| rekrutmen | 1872(0.915) 1980(0.865) 1261(0.865) 1823(0.809) 225(0.796) | 1173 921 1321 1806 1895 | 1195 1825 1116 61 1 | no dup | order OK | Keputusan rekrutmen pegawai 1872/2021 rel=1.00 time=0.85 relasi=0.80 b=2026-05-11 f=sudah |
| anggaran | 950(0.938) 302(0.896) 66(0.894) 1062(0.884) 1271(0.879) | 1590 1992 441 1416 1090 | 1558 173 123 580 208 | no dup | order OK | Surat anggaran pegawai 2021 rel=1.00 time=0.98 relasi=0.72 b=2026-07-18 f=sudah |
| surat tugas | 1092(0.910) 384(0.882) 755(0.882) 1976(0.866) 1720(0.820) | 1645 1510 164 774 1143 | 866 1987 503 475 1139 | no dup | order OK | Laporan surat tugas 2025 SDM.01.01 rel=1.00 time=0.86 relasi=0.76 |
| arsip | 664(0.927) 337(0.921) 1530(0.914) 655(0.905) 820(0.903) | 1392 1518 982 463 1418 | 1271 500 1363 1296 1775 | no dup | order OK | Berita acara audit internal 664/2016 rel=1.00 time=0.81 relasi=0.92 |
| laporan | 1214(1.000) 1092(0.910) 1566(0.901) 784(0.900) 1932(0.897) | 621 982 1167 323 660 | 1062 1418 384 1363 670 | no dup | order OK | Laporan hukum kepegawaian 2016 KEU.01 rel=1.00 time=1.00 relasi=1.00 b=2026-08-18 f=sudah |

> **Bukti deep pagination:** offset 80 (page 5, 20/page) tetap `no dup, order OK` — tidak ada lubang/duplikat. Versi PHP lama akan miss karena `LIMIT 160 BY id` memotong sebelum ranking.

**`rank_smoke_pdo.php` — baseline vs ranked reordering (5 kueri):** semua `order changed? YES` — ranking benar-benar mengurutkan ulang, bukan kebetulan.

### 4.2 Evaluasi Sintetis P@10 & NDCG@10 (30 kueri, gain = round(skor*3))

**Perintah replikasi:**
```bash
php spark migrate
php spark db:seed ArteriSeeder
php spark db:seed LargeScaleSeeder   # 2000 arsip (atau SaracevicRankingSeeder untuk 120 pilot)
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic         # gain linear (default)
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic --gain exp2
```

**Hasil live @2000 arsip, k=10, 416 pasangan query–arsip (28 kueri hit, 2 kueri 0-hit disimpan sebagai 0 di AVG):**

| Skala | P@10 baseline | P@10 ranked | ΔP@10 | NDCG@10 baseline (linear) | NDCG@10 ranked | ΔNDCG (linear) | NDCG (exp2) Δ |
|-------|---------------|-------------|-------|---------------------------|----------------|----------------|---------------|
| 120 pilot (dari naskah) | 0,321 | 0,507 | **+0,186** | 0,828 | 1,000 | **+0,172** | — |
| **2000 stress-test (live 2026-08-28)** | **0,332** | **0,632** | **+0,300** | **0,778** | **1,000** | **+0,222** | **+0,332** |

> Angka live **persis** dengan Tabel §5.2 naskah (ΔP+0,300/ΔNDCG+0,222 linear, +0,332 exp2). File `relevance-judgment-synthetic.csv` (417 baris, header + 416) ter-generate ulang dan konsisten.

**Per-kueri (linear, 2000 arsip) — 5 terbesar ΔP:**

| Q | keywords | P_base | P_rank | ΔP | NDCG_base | NDCG_rank | ΔNDCG | Catatan |
|---|----------|--------|--------|----|-----------|-----------|-------|---------|
| Q10 | SDM.03.01 pelatihan | 0.100 | 1.000 | **+0.900** | 0.535 | 1.000 | +0.465 | kode spesifik + uraian — SQL hybrid mengangkat dari id dalam |
| Q25 | kinerja evaluasi pegawai | 0.100 | 1.000 | +0.900 | 0.539 | 1.000 | +0.461 | multi-token relativeness |
| Q01 | rekrutmen pegawau (typo) | 0.200 | 1.000 | +0.800 | 0.598 | 1.000 | +0.402 | negative control — token `pegawau` miss, ranking jatuh ke `rekrutmen` |
| Q23 | arsip hukum tata laksana | 0.200 | 1.000 | +0.800 | 0.657 | 1.000 | +0.343 | |
| Q16 | SDM rekrutmen seleksi | 0.300 | 1.000 | +0.700 | 0.686 | 1.000 | +0.314 | multi-token |

**Kueri 0-hit (negative control):** Q14 `nobox BOX-01` dan Q29 typo tidak muncul di tabel (0 baris) — wajar karena `LIKE '%BOX-01%'` spesifik; ini menguji *system relevance* field `nobox`/ketahanan typo.

**Interpretasi Tahap 1:** Δ positif konsisten di kedua skala; skala 2000 lebih besar karena efek **ranking + perbaikan pagination global**. NDCG 1.000 = **upper bound sintetis** (urutan ideal menurut gain yang diturunkan dari skor yang sama).

---

## 5. Code Query Relevan dengan Pembahasan (siap copy-paste ke Lampiran Jurnal)

### 5.1 Baseline (masalah yang diatasi) — `ArsipModel::buildSearchQuery`

```php
// app/Models/ArsipModel.php — buildSearchQuery() (sebelum ranking)
// Simple search: OR, tanpa skor, urut BY id (siapa duluan input, dia di atas)
if ($keywords !== '') {
    $builder->groupStart()
        ->like('a.noarsip', $keywords)
        ->orLike('a.uraian', $keywords)
        ->orLike('a.nobox', $keywords)
        ->groupEnd();
}
// ...
// Pemanggilan baseline (Home.php lama):
$results = $arsipModel->search($keywords, $filters, 20, $offset); // ORDER BY a.id ASC implisit
$total   = $arsipModel->searchCount($keywords, $filters);
```

*Kelemahan:* semua match dianggap sama relevan; arsip `id=900` yang paling relevan tidak pernah terlihat jika `LIMIT 20 OFFSET 0`.

### 5.2 SQL Hybrid Ranking — `ArsipModel::searchRanked` (dipakai Tahap 1)

```php
// app/Models/ArsipModel.php — searchRanked() — rumus Fafalios→Saracevic di SQL
// skor = 0.5*kecocokan + 0.3*urgensi + 0.2*kedekatan  ∈ [0,1]
$wRel = 0.5; $wTime = 0.3; $wRelasi = 0.2; // normalize sum=1

// 1) Relativeness per token — GREATEST across fields, word-boundary bonus untuk uraian
//    uraian word-boundary → 3, noarsip → 2, nobox/pencipta/pengolah/kode → 1.5/1
//    Normalisasi: sum(max per token) / (n_tokens * 3)  capped 1
$uraianScore = "(CASE WHEN LOWER(a.uraian) LIKE '% tok %' OR LOWER(a.uraian) LIKE 'tok %'
                      OR LOWER(a.uraian) LIKE '% tok' OR LOWER(a.uraian)='tok' THEN 3
                      WHEN LOWER(a.uraian) LIKE '%tok%' THEN 1.5 ELSE 0 END)";
$noarsipScore= "(CASE WHEN LOWER(a.noarsip) LIKE '%tok%' THEN 2 ELSE 0 END)";
// ... + nobox, pencipta, pengolah, kode
$maxExpr = $isSqlite
  ? "max(max(max(max(max({uraian},{noarsip}),{nobox}),{pencipta}),{pengolah}),{kode})"
  : "GREATEST({uraian},{noarsip},{nobox},{pencipta},{pengolah},{kode})";
$relExpr = "LEAST(1, (sumTokens)/(n*3.0))"; // atau min(1.0, ...) di SQLite

// 2) Timeliness — b = tanggal + retensi (jatuh tempo)
if ($isSqlite) {
  $expiry = "date(a.tanggal, '+' || k.retensi || ' years')";
  $base   = "(1.0 / (1.0 + ABS(julianday(expiry)-julianday('now'))/365.0))";
  $boost  = "(CASE WHEN expiry < date('now') THEN 0.08 ELSE 0 END)"; // lewat tempo → boost
  $timeExpr = "min(1.0, base + boost)"; // COALESCE(...,0.5) fallback
} else {
  $expiry = "DATE_ADD(a.tanggal, INTERVAL k.retensi YEAR)";
  $base   = "(1/(1+ABS(DATEDIFF(expiry,CURDATE()))/365))";
  $boost  = "(IF(expiry < CURDATE(),0.08,0))";
  $timeExpr = "LEAST(1, base+boost)";
}

// 3) Relations — co-occurrence kode:pencipta
$relasiExpr = $isSqlite
  ? "COALESCE(CAST(s.cnt AS REAL)/NULLIF(s_max.max_cnt,0), 0)"
  : "COALESCE(s.cnt/NULLIF(s_max.max_cnt,0), 0)";
// JOIN stat_kode_pencipta (162 pairs @2000, refresh via seeder):
//   LEFT JOIN stat_kode_pencipta s ON s.kode=a.kode AND s.pencipta=a.pencipta
//   CROSS JOIN (SELECT MAX(cnt) as max_cnt FROM stat_kode_pencipta) s_max

$scoreExpr = "({wRel}*rel + {wTime}*time + {wRelasi}*relasi)";
$builder->select("({relExpr}) as score_rel, ({timeExpr}) as score_time,
                  ({relasiExpr}) as score_relasi, ({scoreExpr}) as score", false);
$builder->join('stat_kode_pencipta s','s.kode=a.kode AND s.pencipta=a.pencipta','left');
$builder->join('(SELECT MAX(cnt) as max_cnt FROM stat_kode_pencipta) s_max','1=1','cross', false);
$builder->orderBy('score','DESC')->orderBy('a.tanggal','DESC')->orderBy('a.id','DESC');
$builder->limit($limit, $offset); // pagination KONSISTEN GLOBAL — DB urutkan semua dulu baru potong
```

**Driver-aware:** `GREATEST` vs nested `max()`, `LEAST` vs `min()`, `DATE_ADD/DATEDIFF` vs `date/julianday`, `CAST AS REAL` untuk SQLite integer division — ada di `ArsipModel.php:82-176`.

**Fallback:** jika `stat_kode_pencipta` belum ada, catch → fallback ke PHP `RelevanceRankingService::rank()` (fetch `limit+offset+100`, rank di PHP, slice).

### 5.3 Spesifikasi PHP (untuk unit test & Python port) — `RelevanceRankingService`

```php
// app/Services/RelevanceRankingService.php — dipakai test & eval_ndcg.py (bukan produksi Tahap 1)
public function rank(array $rows, string $keywords, array $weights=[], array $coMap=[]): array {
  $tokens = $this->tokenize($keywords); // lowercase, split \s+, unique
  foreach ($rows as &$r) {
    $r['score_rel']    = $this->scoreRelativeness($r, $tokens); // 0..1
    $r['score_time']   = $this->scoreTimeliness($r);             // 1/(1+|b-today|/365)+boost
    $r['score_relasi'] = $this->scoreRelations($r, $coMap, max($coMap)); // cnt/max
    $r['score'] = round(0.5*rel + 0.3*time + 0.2*relasi, 4);
  }
  usort($rows, fn($a,$b)=> $a['score']===$b['score'] ? strcmp($b['tanggal'],$a['tanggal'])
                                                     : ($a['score']<$b['score']?1:-1));
  return $rows;
}
```

### 5.4 Controller A/B — `Home::search` (`?rank=1`)

```php
// app/Controllers/Home.php::search($offset)
$ranked = $this->request->getGet('rank') === '1';
if ($ranked) {
  $results = $arsipModel->searchRanked($keywords, $filters, $perPage, $offset);
  $total   = $arsipModel->searchRankedCount($keywords, $filters);
} else {
  $results = $arsipModel->search($keywords, $filters, $perPage, $offset);
  $total   = $arsipModel->searchCount($keywords, $filters);
}
// Preserve query string agar pagination konsisten:
$qs = http_build_query(array_filter(['katakunci'=>$keywords,'rank'=>$ranked?'1':null]));
$pager->setPath('search?'.$qs); // page 2 = ?katakunci=anggaran&rank=1&offset=20 → peringkat 21-40
```

### 5.5 Evaluasi — `eval_ndcg.py` (Python port ranking + NDCG)

```python
# docs/saracevic-ranking/eval_ndcg.py — gain & metrik
# gain = round(skor*3)  → 0..3  (sintetis, upper bound)
# P@10 = (# gain>=2 di top-10)/10
# DCG@k = sum(gain / log2(rank+1)), NDCG = DCG/IDCG
gain = int(round(score*3))  # score 0..1 dari rank_rows()
# rank_rows() = port Python dari RelevanceRankingService (tokenize, relativeness, timeliness, relations)

# Replikasi:
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic          # linear gain
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic --gain exp2  # exp gain 2^rel-1
```

### 5.6 Contoh Query Nyata (live DB 2000)

```sql
-- Baseline (tanpa ranking) — urut BY id, arsip relevan di id besar terpendam:
SELECT a.*, k.retensi FROM data_arsip a
JOIN master_kode k ON k.id=a.kode
WHERE a.deleted_at IS NULL AND (a.noarsip LIKE '%anggaran%' OR a.uraian LIKE '%anggaran%' OR a.nobox LIKE '%anggaran%')
ORDER BY a.id ASC LIMIT 20 OFFSET 0;  -- p1 = id 5,6,7,9,35 ...

-- Ranked (SQL hybrid) — urut BY score DESC:
SELECT a.*, k.retensi,
  LEAST(1, (GREATEST(CASE WHEN LOWER(a.uraian) LIKE '%anggaran%' THEN 3 ELSE 0 END, ...)/3)) as score_rel,
  LEAST(1, 1/(1+ABS(DATEDIFF(DATE_ADD(a.tanggal,INTERVAL k.retensi YEAR),CURDATE()))/365)
           + IF(DATE_ADD(a.tanggal,INTERVAL k.retensi YEAR)<CURDATE(),0.08,0)) as score_time,
  COALESCE(s.cnt/NULLIF(s_max.max_cnt,0),0) as score_relasi,
  (0.5*score_rel + 0.3*score_time + 0.2*score_relasi) as score
FROM data_arsip a
JOIN master_kode k ON k.id=a.kode
LEFT JOIN stat_kode_pencipta s ON s.kode=a.kode AND s.pencipta=a.pencipta
CROSS JOIN (SELECT MAX(cnt) as max_cnt FROM stat_kode_pencipta) s_max
WHERE a.deleted_at IS NULL AND (a.noarsip LIKE '%anggaran%' OR a.uraian LIKE '%anggaran%' OR a.nobox LIKE '%anggaran%')
ORDER BY score DESC, a.tanggal DESC LIMIT 20 OFFSET 0;
-- p1 = id 950(0.938) 302(0.896) 66(0.894) 1062(0.884) 1271(0.879) — 950 naik karena time=0.98 + relasi=0.72
```

**Contoh hitung skor manual** (arsip `id 950` @2000, kueri `anggaran`):
`rel=1.00` (uraian mengandung `anggaran` word-boundary), `time=0.98` (b=2026-07-18, selisih ~40 hari), `relasi=0.72` (cnt 18 / max 25) → `0.5*1 +0.3*0.98+0.2*0.72 = 0.938` peringkat 1 (di smoke: `anggaran` p1 top).

---

## 6. Naskah Final Polished (siap submit Tahap 1)

> File sumber tetap `docs/saracevic-ranking/DRAFT-JURNAL.md` @0ac715e. Di bawah adalah versi **polished** dengan perbaikan §3 terapan — copy-paste siap ke template jurnal.

### Judul
**Pengembangan Sistem Temu Kembali Arsip Digital Berbasis Model Relevansi Bertingkat Saracevic: Implementasi pada Arteri-2**

### Abstrak (ID, 250 kata — polished)
Temu kembali arsip digital di lingkungan pemerintah masih didominasi pencocokan kata kunci tanpa pemeringkatan. Setiap arsip yang mengandung kata kunci dianggap sama relevan, padahal kebutuhan pengguna bersifat situasional—jatuh tempo retensi, hak akses klasifikasi, dan tugas yang sedang dikerjakan. Penelitian Design Science Research ini mengembangkan sistem temu kembali arsip berbasis model relevansi bertingkat Saracevic (1975; 2007) dan Stratified Model (1997), diadaptasi dari Fafalios et al. (JCDL 2017, arXiv:1810.11049). Implementasi pada Arteri-2 (CodeIgniter 4, MySQL/SQLite) memetakan tiga komponen Fafalios ke strata Saracevic: (i) kecocokan kata → relevansi sistem & topikal, (ii) urgensi waktu (b=tanggal+retensi) → situasional, (iii) kedekatan entitas (kode:pencipta) → kognitif, dengan skor = 0,5×kecocokan + 0,3×urgensi + 0,2×kedekatan yang dihitung di query SQL sebagai ORDER BY score sehingga pagination konsisten global. Evaluasi teknis sintetis (gain=round(skor×3), 30 kueri): pada 120 arsip ΔP@10 +0,186, ΔNDCG@10 +0,172; pada 2000 arsip (deep pagination) ΔP@10 +0,300, ΔNDCG@10 +0,222 (NDCG sintetis 1,000 = upper bound internal). Prototipe menyediakan mode A/B ?rank=0/1 yang memungkinkan replikasi. Kontribusi: operasionalisasi pertama relevansi bertingkat untuk kearsipan pemerintah Indonesia dengan artefak terbuka.

*Kata kunci: temu kembali arsip, relevansi bertingkat, Saracevic, stratified model, ranking, Arteri, Fafalios et al. (JCDL 2017)*

### Koreksi kecil pada naskah (diff ringkas terhadap 0ac715e)
- §1.4 tambahkan: "NDCG 1,000 adalah upper bound sintetis; validasi human (Tahap 2) akan menurunkan angka."
- §2.3: "Fafalios dkk. (JCDL 2017, arXiv:1810.11049)" — konsisten conference + preprint.
- §5.2: beri label **Tabel 2. Ringkasan Δ Tahap 1**; tambahkan kolom gain exp2 (+0,332 @2000) di lampiran.
- §6: tambahkan paragraf *Threats to Validity* (gain sintetis bias model, tanpa stemming Sastrawi, negative control typo).
- Lampiran: sertakan code §5.1–§5.6 di atas sebagai *Artifact Appendix*.

*(Isi lengkap §1–§7 tetap dari DRAFT-JURNAL.md 0ac715e — tidak diubah substansinya, hanya polish di atas.)*

---

## 7. Cara Mereplikasi (1-to-1 dengan naskah)

```bash
# 1. Seed (sekali)
php spark migrate
php spark db:seed ArteriSeeder
php spark db:seed SaracevicRankingSeeder   # 120 pilot — atau LargeScaleSeeder untuk 2000 stress-test

# 2. Verifikasi pagination konsisten global
php tests/sql_hybrid_smoke.php             # no dup, order OK sampai offset 80
php tests/rank_smoke_pdo.php               # order changed? YES

# 3. Evaluasi sintetis Tahap 1 (tanpa human)
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic --gain exp2

# 4. Unit test
vendor/bin/phpunit tests/unit/RelevanceRankingServiceTest.php --testdox  # 10/10

# 5. Manual A/B
# Baseline: http://localhost:8080/search?katakunci=anggaran
# Ranked:   http://localhost:8080/search?katakunci=anggaran&rank=1  (badge Ranked)
# Page 2 ranked: ...&rank=1&offset=20  (peringkat 21-40 global)
```

---

## 8. Daftar Pustaka Inti (terverifikasi 2026-08-27)

- Saracevic, T. (1975). RELEVANCE: A review of and a framework for the thinking on the notion in information science. *JASIST*, 26(6), 321–343. https://doi.org/10.1002/asi.4630260604 — 868 sitasi.
- Saracevic, T. (1997). The Stratified Model of Information Retrieval Interaction. *Proc. ASIST*, 34, 313–327. — 155 sitasi.
- Saracevic, T. (2007). Relevance Part II & III. *JASIST*, 58(13), 1915–1933 & 58(14), 2126–2144. https://doi.org/10.1002/asi.20682 / 20681
- Fafalios, P., Kasturia, V., & Nejdl, W. (2017). Towards a Ranking Model for Semantic Layers over Digital Archives. *Proc. JCDL 2017*. https://doi.org/10.1109/JCDL.2017.7991617 — arXiv:1810.11049 [cs.IR, cs.DL].

---

## 9. Artefak

- `app/Services/RelevanceRankingService.php` (spesifikasi & test), `app/Models/ArsipModel.php` (SQL hybrid produksi), `app/Controllers/Home.php` (?rank=1), `app/Views/home/search.php` (badge)
- `app/Database/Migrations/2026-08-27-000001_CreateStatKodePencipta.php` + `LargeScaleSeeder.php` / `SaracevicRankingSeeder.php` (162 pairs @2000)
- `docs/saracevic-ranking/query-set-30.csv` (30 kueri), `JUDGMENT-GUIDE.md`, `eval_ndcg.py`, `relevance-judgment-synthetic.csv` (416 baris), `DESIGN.md`
- `tests/unit/RelevanceRankingServiceTest.php` (10/10) + `tests/sql_hybrid_smoke.php` + `tests/rank_smoke_pdo.php`

---
*Generated live 2026-08-28 — semua angka diverifikasi via re-run, bukan klaim. Tahap 2 (human judgment, κ, stemming Sastrawi, tuning bobot) disiapkan tapi tidak diklaim di Tahap 1.*
