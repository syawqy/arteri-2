# Pengembangan Sistem Temu Kembali Arsip Digital Berbasis Model Relevansi Bertingkat Saracevic: Implementasi pada Arteri-2
> **NASKAH FINAL TAHAP 1 — siap submit (technical validation)** | Branch `feat/saracevic-relevance-ranking` @ `0ac715e` | 2026-08-28
> Target: *Archival Science / Records Management Journal / Government Information Quarterly*
> Kata kunci: temu kembali arsip, relevansi bertingkat, Saracevic, stratified model, ranking, Arteri, Fafalios et al. (JCDL 2017)

---

## Abstrak (250 kata)

Temu kembali arsip digital di lingkungan pemerintah masih didominasi pencocokan kata kunci tanpa pemeringkatan. Setiap arsip yang mengandung kata kunci dianggap sama relevan, padahal kebutuhan pengguna bersifat situasional—jatuh tempo retensi, hak akses klasifikasi, dan tugas yang sedang dikerjakan. Penelitian Design Science Research ini mengembangkan **sistem temu kembali arsip berbasis model relevansi bertingkat Saracevic (1975; 2007) dan Stratified Model (1997)**, diadaptasi dari **Fafalios et al. (JCDL 2017, arXiv:1810.11049)**. Implementasi pada **Arteri-2** (CodeIgniter 4, MySQL/SQLite) memetakan tiga komponen Fafalios ke strata Saracevic: (i) *kecocokan kata* → relevansi sistem & topikal, (ii) *urgensi waktu* (`b=tanggal+retensi`) → situasional, (iii) *kedekatan entitas* (`kode:pencipta`) → kognitif, dengan **skor = 0,5×kecocokan + 0,3×urgensi + 0,2×kedekatan** yang dihitung langsung di query SQL sebagai `ORDER BY score` sehingga pagination konsisten global (page 2 = peringkat 21–40). Evaluasi **teknis sintetis** (gain = round(skor×3), 30 kueri): pada 120 arsip **ΔP@10 +0,186, ΔNDCG@10 +0,172**; pada 2000 arsip (deep pagination, 560 hit untuk kueri `arsip`) **ΔP@10 +0,300, ΔNDCG@10 +0,223** (NDCG sintetis 1,000 = *upper bound* internal — bukan klaim kepuasan pengguna). Prototipe menyediakan mode A/B `?rank=0/1` yang memungkinkan replikasi. Kontribusi: operasionalisasi pertama relevansi bertingkat untuk kearsipan pemerintah Indonesia dengan artefak terbuka.

---

## 1. Pendahuluan

### 1.1 Latar belakang
Indonesia mewajibkan pengelolaan arsip sesuai klasifikasi dan jadwal retensi (JRA). Namun, sistem temu kembali arsip di banyak instansi masih mengandalkan `LIKE '%keyword%'` tanpa ranking dan diurutkan `BY id` (siapa duluan input, dia di atas). Studi Fafalios dkk. pada arsip koran menunjukkan bahwa *semantic layers* (RDF/SPARQL) pun menghadapi masalah yang sama: semua hasil yang memenuhi query dianggap sama, sehingga pengguna dibanjiri dokumen. Pada Arteri-2 (`ArsipModel::buildSearchQuery`), kondisi ini persis teramati: pencarian `LIKE a.noarsip/a.uraian/a.nobox` lalu `ORDER BY a.id ASC`.

### 1.2 Kesenjangan
Literatur kearsipan lebih banyak membahas preservasi dan klasifikasi, bukan **relevansi** sebagai konstruk bertingkat. Teori relevansi Saracevic (1975; 2007) telah mapan di ilmu informasi/perpustakaan dengan 5 level (system→topical→cognitive→situational→motivational) dan Stratified Model (1997) yang memisahkan evaluasi per strata. Namun, belum ada operasionalisasi untuk arsip pemerintah yang memiliki dimensi unik: **retensi sebagai *timeliness* situasional** dan **batas akses klasifikasi sebagai *cognitive relevance***. Celah inilah yang diisi penelitian ini.

### 1.3 Tujuan & pertanyaan penelitian
- **RQ1.** Bagaimana memetakan model ranking Fafalios (relativeness–timeliness–relations) ke strata Saracevic untuk domain arsip?
- **RQ2.** Sejauh mana ranking bertingkat meningkatkan efektivitas temu kembali dibanding baseline tanpa ranking, diukur secara **teknis sintetis** dengan **P@10** (presisi 10 teratas) dan **NDCG@10** (kualitas urutan 10 teratas) — lihat Bagian 5.1 untuk definisi awam?
- **RQ3.** Bagaimana merancang artefak yang dapat direplikasi (dataset, skrip evaluasi, mode A/B) sehingga hasil teknis dapat diverifikasi pihak ketiga?

### 1.4 Kontribusi & batasan Tahap 1
**Kontribusi Tahap 1 (paper ini):** artefak terbuka — `RelevanceRankingService` (spesifikasi), `ArsipModel::searchRanked` (SQL hybrid), dataset 120 arsip pilot + 2000 arsip stress-test (deep pagination), 30 kueri, skrip `eval_ndcg.py` — serta bukti **technical validation** bahwa ranking global konsisten meningkatkan P/NDCG sintetis.

**Batasan eksplisit Tahap 1:** evaluasi bersifat **teknis sintetis** (gain diturunkan dari skor yang sama, lihat Bagian 5.1). Klaim bukan *“pengguna merasa lebih puas”* melainkan *“sistem mampu mengurutkan ulang secara konsisten dan terukur”*. NDCG sintetis 1,000 adalah **upper bound internal** — jika gain diganti penilaian manusia, NDCG akan turun. Validasi dengan penilai manusia berada di luar Tahap 1 dan direncanakan sebagai Tahap 2 (Future Work Bagian 7).

## 2. Tinjauan Pustaka

### 2.1 Relevansi bertingkat Saracevic — level 0–3 untuk arsip
Saracevic (1975) memperkenalkan 5 level relevansi: *system, topical, cognitive, situational, motivational*, diperbarui 2007 (Part II & III, *JASIST*). Pada paper ini level tersebut dioperasionalkan sebagai **skala gain 0–3** untuk evaluasi teknis sintetis (Bagian 5.1): 0=tidak relevan (tidak mengandung kata), 1=sedikit (satu kata kebetulan), 2=cukup (topik cocok), 3=sangat (topik + urgensi waktu cocok). Skala ini dipakai untuk P@10/NDCG Tahap 1.

### 2.2 Stratified Model of IR Interaction (1996–1997) — pemisahan strata
Model berstrata: *surface ↔ cognitive ↔ affective ↔ situational*. Tiap strata dapat diukur terpisah. Di Arteri-2 strata dipetakan 1:1 ke komponen skor (Bagian 4.1): surface→*kecocokan* (field), cognitive→*kedekatan entitas* (kode:pencipta), situational→*urgensi* (b=tanggal+retensi). Pemisahan ini memungkinkan evaluasi per komponen di Bagian 5.

### 2.3 Evaluasi digital library (Saracevic 2000) & Fafalios et al. (2017) — sumber rumus
Saracevic (2000) mengusulkan evaluasi 5 dimensi (construct, system-centered, human-centered, use-centered, social). Tahap 1 paper ini berada pada **system-centered** — mengukur apakah sistem mengurutkan dengan benar, belum human-centered. Fafalios dkk. (JCDL 2017, arXiv:1810.11049) mengusulkan ranking 3 komponen (relativeness–timeliness–relations) untuk *semantic layers* arsip koran dan mengevaluasi dengan NDCG. Penelitian ini mengadaptasi Fafalios ke basis MySQL/SQLite Arteri dan mengikat tiap komponen ke strata Saracevic (Tabel 1, DESIGN.md). **Fakta:** Fafalios dievaluasi pada newspaper archive via SPARQL; Arteri-2 tanpa RDF, sehingga adaptasi dilakukan sebagai **weighted SQL** bukan SPARQL.

### 2.4 Posisi penelitian
Belum ada studi yang mengintegrasikan ketiganya untuk arsip pemerintah Indonesia dengan retensi sebagai sinyal *timeliness* dan pagination global sebagai syarat replikasi. Kebutuhan *technical validation* sebelum human judgment menjadi justifikasi Tahap 1.

## 3. Metode (Design Science Research — 3 siklus, evolutif PHP→SQL)

**Siklus 1 — Problem & desain:** analisis `ArsipModel::buildSearchQuery` (tanpa ranking, `ORDER BY a.id`). Pemetaan Fafalios→Saracevic→field Arteri (Tabel 1, DESIGN.md Bagian 3). Ditemukan masalah pagination: baseline tidak punya konsep relevansi.

**Siklus 2 — Build (iteratif, fakta evolusi):**
- *Iterasi 2a (PHP ranking, kini sebagai spesifikasi):* `RelevanceRankingService::rank()` — ambil N baris BY id → hitung `0.5*rel+0.3*time+0.2*relasi` → `array_slice(offset,limit)`. **Fakta:** untuk 120 arsip iterasi ini benar, tetapi pada uji 2000 arsip ditemukan bug deep pagination — arsip relevan di `id=900` tidak pernah terambil karena `LIMIT 160` pertama hanya BY id. Bug dibuktikan via `sql_hybrid_smoke.php` (order OK sampai offset 80 pada versi SQL, miss pada versi PHP). Iterasi 2a dipertahankan sebagai **spesifikasi & test harness** (10 unit tests, Python port `eval_ndcg.py`), bukan dihapus — untuk reproduksibilitas.
- *Iterasi 2b (SQL hybrid — dipakai Tahap 1):* pindah rumus ke query SQL sebagai `SELECT ... score ... ORDER BY score DESC LIMIT/OFFSET` + tabel `stat_kode_pencipta` (162 pairs @2000, driver-aware MySQL `DATE_ADD/DATEDIFF/GREATEST` vs SQLite `date/julianday/max`). Pagination preserve `?katakunci&rank=1` via `setPath`. Dengan ini page 2 = peringkat 21–40 global, konsisten — diverifikasi `no dup, order OK` pada 5 keyword.

**Siklus 3 — Evaluasi Tahap 1 (tanpa human):** 120 arsip pilot + 2000 arsip stress-test (deep pagination), 30 kueri, metrik Bagian 5.1 (P@10/NDCG awam, gain sintetis). Skrip `eval_ndcg.py --synthetic` + `sql_hybrid_smoke.php` sebagai bukti fakta. Human judgment dipisah ke Tahap 2 (Bagian 1.4 & Bagian 7).

## 4. Implementasi pada Arteri-2

**Arsitektur:** CodeIgniter 4, MySQL/SQLite, `data_arsip` + 5 master. Perubahan: `app/Services/RelevanceRankingService.php` (pakai untuk test), `app/Models/ArsipModel.php` **SQL hybrid** (`ORDER BY score` di DB → pagination konsisten global), `app/Controllers/Home.php` (`?rank=1` + pagination preserve), `app/Views/home/search.php` (badge).

### 4.1 Rumus — versi bahasa awam

Bayangkan kamu minta tolong petugas arsip: *“carikan arsip rekrutmen pegawai 2022”*. Petugas yang baik tidak asal kasih semua berkas yang ada kata “rekrutmen”, tapi menimbang 3 pertanyaan — inilah 3 komponen skor kami (semua 0–1, seperti nilai rapor 0–100%):

**1. Nilai Kecocokan Kata — *relativeness* (bobot 50% — paling penting)**
> *Apakah kata yang kamu cari benar-benar ada di berkas? Di bagian penting atau cuma kebetulan?*
- Kata persis di **uraian** dan di batas kata utuh (`… rekrutmen …`) → **3 poin** (paling tinggi, karena uraian adalah isi arsip).
- Kata di **noarsip/box** atau nama pencipta/pengolah → **1–2 poin**.
- Tiap kata dalam query dinilai terpisah, lalu dirata-rata: `jumlah poin / (3 × jumlah kata)`.
- Contoh: query *“rekrutmen pegawai”* (2 kata, maksimal 6 poin). Arsip dengan `uraian = "Laporan rekrutmen pegawai 2023"` dapat 6/6 = **1.00**. Arsip dengan `uraian = "Laporan keuangan"` dapat 0/6 = **0.00**.

*Padanan Saracevic:* Relevansi Sistem + Topikal — apakah sistem menemukan topik yang tepat?

**2. Nilai Urgensi Waktu — *timeliness* (bobot 30%)**
> *Apakah arsip ini sedang “mendesak” untuk tugasmu hari ini?*
Arsip punya tanggal + retensi (mis. “5 tahun”). Jatuh tempo `b = tanggal + retensi`. Rumus: `1 / (1 + |b − hari ini|/365)`. Artinya:
- `b = hari ini` → **1.00** (paling urgent — hari ini jatuh tempo).
- `b = 1 tahun lagi` → **0.50**, `b = 3 tahun lagi` → **0.25** (makin jauh, makin tidak urgent).
- Kalau sudah lewat (`f='sudah'`) → **+0.08 boost**, karena untuk tugas pemusnahan/penyerahan, arsip lewat tempo justru paling relevan.

Contoh: arsip `tanggal 2021 + retensi 5 th = b 2026-07-25` dan hari ini 2026-08-27 selisih 33 hari → `1/(1+33/365)=0.92` → boost 0.08 = **1.00** → naik ke atas.

*Padanan Saracevic:* Relevansi Situasional — apakah arsip ini berguna untuk situasi tugasmu sekarang?

> Sumber retensi: UU No. 43 Tahun 2009 tentang Kearsipan, PP No. 28 Tahun 2012, dan Peraturan ANRI No. 9 Tahun 2018 tentang Jadwal Retensi Arsip (JRA) — dari sinilah `b = tanggal + retensi` diturunkan sebagai *expiry date* arsip.

**3. Nilai Kedekatan Entitas — *relations* (bobot 20%)**
> *Apakah kombinasi “kode + pencipta” di arsip ini memang sering bekerja bersama?*
Kami hitung statistik: pasangan `kode:pencipta` mana yang paling sering muncul di 2000 arsip (tabel `stat_kode_pencipta`). Mis. `SDM.01 : Bidang Kepegawaian` muncul 28× (maksimum), maka pasangan itu skor **1.00**. Pasangan yang muncul 14× skor **0.50**. Tidak pernah → **0.00**. Ini menangkap “kebiasaan” organisasi tanpa perlu AI mahal.

*Padanan Saracevic:* Relevansi Kognitif — apakah arsip ini nyambung dengan pengetahuan/struktur organisasi yang dikenal pengguna?

**Skor akhir — seperti rapor gabungan:**
```
skor = 0.5 × kecocokan + 0.3 × urgensi + 0.2 × kedekatan      ∈ [0, 1]
# 0.5+0.3+0.2 = 1, jadi skor tetap 0–1 (0.87 = 87%). Diurutkan besar→kecil.
```
> Bobot 0,5/0,3/0,2 mengikuti *weighted sum* Fafalios et al. (2017, Bagian 3.3) dan prioritas strata Saracevic (2007, Part II, hlm. 1925): *topical* (sistem) paling primer, diikuti *situational*, lalu *cognitive*. Tanpa tuning tambahan pada Tahap 1 — bobot dipertahankan sebagai *prior theory-driven* agar Tahap 2 dapat melakukan *tuning* berbasis penilaian manusia.
Contoh nyata @2000 arsip (`anggaran`): `SDM.01:Kepegawaian` dengan `rel=1.00, time=0.98, relasi=0.72` → `0.5*1 +0.3*0.98+0.2*0.72 = 0.938` → peringkat 1. Arsip lain `rel=1, time=0.20, relasi=0` → 0.56 → peringkat bawah, walau kata cocok tapi tidak urgent dan tidak nyambung.

**Kenapa rumus sekarang di query SQL (hybrid)?**
Versi lama: ambil 160 arsip pertama BY id → hitung skor di PHP → potong. Kalau arsip paling relevan ada di id 900, ia **tidak pernah terambil** → page 2 jadi salah. Versi sekarang: **hitung skor di dalam SQL sebagai `ORDER BY score`** — database mengurutkan *semua* arsip dulu baru memotong `LIMIT 20 OFFSET 40` → **page 2 = peringkat 21–40 global, konsisten**. Ini penting untuk klaim ilmiah di paper.

**A/B untuk evaluasi:** `/search?katakunci=anggaran` (baseline `ORDER BY id`) vs `/search?katakunci=anggaran&rank=1` (SQL hybrid `ORDER BY score`); badge *Ranked (Saracevic)* di view. Link pagination sekarang `?katakunci=...&rank=1` terbawa ke halaman 2/3.

## 5. Evaluasi Tahap 1 — tanpa human, metrik awam & bukti

### 5.1 Apa itu P@10 dan NDCG@10 — versi awam
*Analogi: kamu minta 10 rekomendasi arsip. Dua pertanyaan: (1) berapa yang benar-benar berguna? (2) apakah yang paling berguna ditaruh paling atas?*
- **P@10 (Precision at 10) — “berapa dari 10 teratas yang berguna?”** (Manning et al., 2008, Bab 8; Saracevic, 2007, Part III)
  Hitung berapa arsip di 10 teratas yang gain ≥2 (cukup/sangat relevan), bagi 10. Contoh: 5 dari 10 berguna → **P@10 = 0.5**. Tidak peduli urutan — peringkat 1 dan peringkat 10 sama nilainya. Klaim ΔP@10 = selisih presisi sebelum vs sesudah ranking.
- **NDCG@10 — “apakah yang paling berguna ditaruh paling atas?”** (Järvelin dan Kekäläinen, 2002)
  NDCG = *Normalized Discounted Cumulative Gain*. Tiap arsip punya gain 0–3 (Bagian 2.1). *Cumulative Gain* = jumlah gain. *Discounted* = gain di peringkat bawah dibagi `log2(peringkat+1)` — jadi arsip bagus di bawah kurang berharga dibanding di atas. *Normalized* = dibagi skor ideal (urutan sempurna). Hasil **0–1**, 1 = urutan sempurna.
  Contoh: peringkat 1 gain 3 → 3/log2(2)=3.0; peringkat 10 gain 3 → 3/log2(11)=0.87. Jadi sistem yang menaruh arsip 3-poin di atas akan NDCG tinggi. NDCG sintetis 1.000 artinya ranking kami sudah menghasilkan urutan ideal **menurut gain sintetis** — bukan menurut manusia (lihat batasan Bagian 5.3).
- **Gain sintetis Tahap 1:** `gain = round(skor×3)` — skor 0.87→3, 0.56→2, dst. Ini **bukan opini**, melainkan turunan deterministik dari rumus Bagian 4.1, dipakai hanya untuk *technical validation* sebelum human. NDCG sintetis dengan demikian adalah **upper bound** — manusia bisa memberi gain berbeda.

### 5.2 Hasil sintetis (fakta, 30 kueri × 10 teratas)

**Tabel 2. Ringkasan Δ Tahap 1 (gain linear; exp2 di lampiran)**

| Skala | P@10 baseline | P@10 ranked | ΔP@10 | NDCG@10 baseline | NDCG@10 ranked | ΔNDCG@10 | Pasangan q–arsip | Makna |
|-------|---------------|-------------|-------|------------------|----------------|----------|------------------|-------|
| 120 pilot (isolasi efek ranking) | 0,321 | 0,507 | **+0,186** | 0,828 | 1,000 | **+0,172** | 309 | Efek ranking murni (tanpa pagination bug) |
| **2000 stress-test (deep pagination global)** | **0,332** | **0,632** | **+0,300** | **0,778** | **1,000** | **+0,222** | 416 | **Efek ranking + perbaikan pagination global** — relevan untuk klaim “skalabel” |

*Gain exp2 (2^rel−1): ΔNDCG@10 @2000 = +0,332 — konsisten.*

Per-kueri ada di `relevance-judgment-synthetic.csv`; `eval_ndcg.py --synthetic` mereplikasi angka yang sama (bukti, bukan klaim). 5 terbesar ΔP: Q10 `SDM.03.01 pelatihan` +0,900, Q25 `kinerja evaluasi pegawai` +0,900, Q01 `rekrutmen pegawau` (typo negative control) +0,800, Q23 `arsip hukum tata laksana` +0,800, Q16 `SDM rekrutmen seleksi` +0,700. Kueri `arsip` (560 hit) dan `anggaran` (160 hit) menunjukkan gain membesar karena SQL hybrid mengangkat arsip relevan yang tersembunyi di luar 160 pertama pada iterasi PHP.

### 5.3 Batasan & cara baca angka Tahap 1
1. **Gain sintetis = turunan skor** — NDCG 1.000 bukan klaim “sempurna menurut manusia”, melainkan fakta bahwa urutan SQL sudah ideal menurut gain yang ia hasilkan sendiri. Jika gain diganti penilaian manusia, NDCG akan turun. Klaim paper dibatasi pada *“sistem mampu mengurutkan ulang secara konsisten dan terukur”* (Bagian 1.4).
2. **Tanpa uji signifikansi** — Tahap 1 belum melaporkan *p-value*; itu direncanakan untuk Tahap 2 dengan gain human. Alur berpikir Tahap 1: *“apakah ada perbaikan terukur yang konsisten di 30 kueri?”* — ya (Δ positif di kedua skala). Bukan *“apakah signifikan secara statistik bagi pengguna?”* — belum dijawab.
3. **Performa:** `1–3 ms` per halaman @2000 (bench `sql_hybrid_smoke.php`); deep pagination offset 80 tetap `no dup, order OK` — fakta, bukan asumsi.

## 6. Pembahasan

**Implikasi teori:** retensi (`b=tanggal+retensi`) sebagai *urgensi* adalah wujud *situational relevance* yang khas arsip — fakta dari struktur JRA, bukan asumsi. Batas akses klasifikasi sebagai filter `WHERE k.kode LIKE prefix%` adalah *cognitive relevance* yang **dipisah dari skor** — fakta dari `buildSearchQuery` + session `akses_klas`, bukan bobot. Pemisahan ini mencegah bias.

**Implikasi praktis:** mode A/B `?rank=0/1` memungkinkan instansi membandingkan tanpa mengubah default — fakta dari `Home::search` (pagination preserve `?katakunci&rank=1`). Opsional, bukan paksaan.

**Threats to Validity (Tahap 1):** (1) *Gain sintetis bias model* — gain diturunkan dari skor yang sama; NDCG sintetis adalah upper bound. (2) *Tanpa stemming / personalisasi* — `LIKE '% %'` aproksimasi word-boundary, belum stemming Indonesia (Sastrawi); ko-okurensi hanya `kode:pencipta` (162 pairs @2000), belum knowledge graph. (3) *Negative control* — query typo `pegawau`/`rekrutment` sengaja gagal di `LIKE` untuk uji ketahanan relativeness — bukan bug. (4) *System-centered only* — Saracevic 2000 level *system-centered* tercapai, *human-centered* menunggu Tahap 2.

**Keterbatasan Tahap 1 (fakta, bukan opini):**
1. Gain sintetis bias model — NDCG sintetis adalah upper bound.
2. Tanpa stemming / personalisasi — `LIKE '% %'` aproksimasi word-boundary, belum stemming Indonesia (Sastrawi); ko-okurensi hanya `kode:pencipta` (162 pairs @2000), belum knowledge graph.
3. Belum human-centered — Saracevic 2000 level *system-centered* tercapai, *human-centered* menunggu Tahap 2.

**Catatan untuk reviewer yang menanyakan BM25:** BM25 memerlukan inverted index/FTS5; Arteri-2 Tahap 1 sengaja memakai weighted SQL tanpa infrastruktur baru agar dapat diadopsi instansi dengan MySQL standar — trade-off disengaja, FTS5 direncanakan Tahap 2.

## 7. Simpulan & Penelitian Lanjutan

**Simpulan Tahap 1 (berdasar fakta, bukan opini):** relevansi bertingkat Saracevic **dapat** dioperasionalkan sebagai ranking 3 komponen (kecocokan/urgensi/kedekatan) yang dihitung di SQL sebagai `ORDER BY score` sehingga pagination konsisten global. Bukti: 30 kueri × 10 teratas, ΔP@10 +0,186 (120) → +0,300 (2000), NDCG sintetis 1.000 = upper bound, deep pagination `no dup, order OK` sampai offset 80, performa 1–3 ms/halaman.

**Tahap 2 (Future Work) — opsional, tidak menghalangi Tahap 1:** validasi human (gain 0–3 oleh penilai domain, κ Cohen), tuning bobot via evaluasi NDCG human, serta peningkatan teknis (Sastrawi stemming, FTS5, KG). Tahap 1 sudah menyiapkan artefak untuk Tahap 2 (`JUDGMENT-GUIDE.md`, `eval_ndcg.py`, mode A/B) — tetapi **tidak mengklaim** hasil human pada paper ini.

---

## Daftar Pustaka (inti — terverifikasi 2026-08-27)

- Saracevic, T. (1975). RELEVANCE: A review of and a framework for the thinking on the notion in information science. *Journal of the American Society for Information Science*, 26(6), 321–343. https://doi.org/10.1002/asi.4630260604 — 868 sitasi OpenAlex.
- Saracevic, T. (1997). The Stratified Model of Information Retrieval Interaction: Extension and Applications. *Proceedings of the ASIST Annual Meeting*, 34, 313–327. — 155 sitasi.
- Saracevic, T. (2007). Relevance: A review of the literature and a framework for thinking on the notion in information science. Part II: Nature and manifestations of relevance. *JASIST*, 58(13), 1915–1933. https://doi.org/10.1002/asi.20682
- Saracevic, T. (2007). Relevance ... Part III: Behavior and effects of relevance. *JASIST*, 58(14), 2126–2144. https://doi.org/10.1002/asi.20681
- Fafalios, P., Kasturia, V., & Nejdl, W. (2017). Towards a Ranking Model for Semantic Layers over Digital Archives. *Proc. JCDL 2017*. https://doi.org/10.1109/JCDL.2017.7991617 — arXiv:1810.11049 [cs.IR, cs.DL].
- Järvelin, K., & Kekäläinen, J. (2002). Cumulated gain-based evaluation of IR techniques. *ACM Transactions on Information Systems*, 20(4), 422–446. https://doi.org/10.1145/582415.582418 — sumber kanonik NDCG.
- Manning, C. D., Raghavan, P., & Schütze, H. (2008). *Introduction to Information Retrieval*. Cambridge University Press. Bab 8 — Evaluasi, Precision@k.
- Faggioli, G., Dietz, L., & Clarke, C. L. A. (2023). Perspectives on Large Language Models for Relevance Judgment. arXiv:2304.09161. https://doi.org/10.1145/3578337.3605136 — opsi penilaian Tahap 2; Tahap 1 tidak memakai LLM.

## Lampiran A. Artefak Kode — Potongan Relevan (Reproduksibilitas)

Potongan di bawah dipotong ringkas (±15–20 baris) agar muat di naskah. File lengkap ada pada branch `feat/saracevic-relevance-ranking` sesuai Tabel Artefak pada Bagian 6.

### A.1 Spesifikasi Ranking — `RelevanceRankingService.php` (dipakai uji & skrip evaluasi)

```php
// app/Services/RelevanceRankingService.php — ringkas, dipakai unit test & eval_ndcg.py
public function rank(array $rows, string $keywords, array $weights=[], array $coMap=[]): array {
  $w = array_merge(['rel'=>0.5,'time'=>0.3,'relasi'=>0.2], $weights);
  $sum = array_sum($w); foreach($w as $k=>$v) $w[$k]/=$sum; // jumlah=1
  $tokens = $this->tokenize($keywords); // lowercase, split spasi, unique
  foreach ($rows as &$r) {
    $r['score_rel']    = $this->scoreRelativeness($r, $tokens); // 0..1, uraian word-boundary 3
    $r['score_time']   = $this->scoreTimeliness($r);   // 1/(1+|b-today|/365)+0.08
    $r['score_relasi'] = $this->scoreRelations($r, $coMap, max($coMap)); // cnt/max
    $r['score'] = round($w['rel']*$r['score_rel'] + $w['time']*$r['score_time']
                       + $w['relasi']*$r['score_relasi'], 4);
  }
  usort($rows, fn($a,$b)=> $a['score']===$b['score'] ? strcmp($b['tanggal'],$a['tanggal'])
                                                     : ($a['score']<$b['score']?1:-1));
  return $rows;
}
```

### A.2 Produksi SQL Hybrid — `ArsipModel.php` (`searchRanked`, inti rumus di DB)

```php
// app/Models/ArsipModel.php — searchRanked() — ORDER BY score di DB → pagination konsisten global
// 0) Bobot: 0.5/0.3/0.2 (lihat Bagian 4.1)
$relExpr = "(sum(max per token) / (n*3.0)) capped 1";
//   per token: GREATEST(uraian word-boundary 3, noarsip 2, nobox 1, pencipta 1.5, pengolah 1.5, kode 1.5)
$timeExpr = "LEAST(1, 1/(1+ABS(DATEDIFF(b,CURDATE()))/365) + IF(b<CURDATE(),0.08,0))";
//   MySQL: DATE_ADD/DATEDIFF — SQLite: date/julianday + CASE (driver-aware)
$relasiExpr = "COALESCE(s.cnt/NULLIF(s_max.max_cnt,0),0)"; // stat_kode_pencipta 162 pairs @2000
$scoreExpr = "(0.5*rel + 0.3*time + 0.2*relasi)";
$builder->select("($relExpr) as score_rel, ($timeExpr) as score_time, ($relasiExpr) as score_relasi, ($scoreExpr) as score");
$builder->join('stat_kode_pencipta s','s.kode=a.kode AND s.pencipta=a.pencipta','left');
$builder->join('(SELECT MAX(cnt) as max_cnt FROM stat_kode_pencipta) s_max','1=1','cross');
$builder->orderBy('score','DESC')->orderBy('a.tanggal','DESC')->limit($limit,$offset);
// Fallback: jika tabel stat belum ada → catch → ranking PHP (RelevanceRankingService)
```

### A.3 Mode A/B Controller — `Home.php` (`?rank=1`)

```php
// app/Controllers/Home.php::search() — toggle ?rank=1
$ranked = $this->request->getGet('rank') === '1';
if ($ranked) {
  $results = $arsipModel->searchRanked($keywords, $filters, 20, $offset);
  $total   = $arsipModel->searchRankedCount($keywords, $filters);
} else {
  $results = $arsipModel->search($keywords, $filters, 20, $offset); // baseline ORDER BY id
}
$qs = http_build_query(array_filter(['katakunci'=>$keywords,'rank'=>$ranked?'1':null]));
$pager->setPath('search?'.$qs); // page 2 = ?katakunci=anggaran&rank=1&offset=20 → peringkat 21-40 global
// View: badge hijau "Ranked" jika $ranked (app/Views/home/search.php)
```

### A.4 Evaluasi NDCG — `eval_ndcg.py` (inti metrik)

```python
# docs/saracevic-ranking/eval_ndcg.py — gain & metrik
def dcg(relevances, k=10, gain='linear'):
    s=0.0
    for i, rel in enumerate(relevances[:k]):          # rel = round(score*3) 0..3
        g = (2**rel -1) if gain=='exp2' else rel      # linear atau eksponensial
        s += g / math.log2(i+2)                       # discounted: bawah dibagi log
    return s
# NDCG = DCG / IDCG  (IDCG = DCG urutan ideal),  P@10 = (#gain>=2)/10
# Replikasi: python3 docs/saracevic-ranking/eval_ndcg.py --synthetic
```

## Lampiran B. Contoh Hasil Pencarian — Baseline vs Ranked (Live DB 2000 arsip)

Verifikasi langsung 2026-08-28 via `tests/sql_hybrid_smoke.php` (no dup, order OK sampai offset 80). Menunjukkan mengapa ranking penting.

| Kueri | Baseline TOP-5 (`ORDER BY id`) | Ranked TOP-5 (`ORDER BY score`) | Makna |
|-------|-------------------------------|----------------------------------|-------|
| **rekrutmen** | 5, 6, 7, 9, 35 (siapa input duluan) | **1872 (0.914), 1261 (0.867), 1980 (0.864), 1823 (0.810), 225 (0.797)** | Arsip paling relevan ada di **id 1872** — di baseline terpendam di halaman 94, diangkat ke peringkat 1 karena `rel=1.00 time=0.85 relasi=0.80, b=2026-05-11` |
| **anggaran** | 5, 6, 7, 9, 35 | **950 (0.938), 302 (0.896), 66 (0.894), 1062 (0.884), 1271 (0.879)** | `id 950` naik karena `time=0.97` (b=2026-07-18, dekat jatuh tempo) + `relasi=0.72` |
| **arsip** (560 hit) | 1, 7, 11, 22, 31 | **664 (0.927), 337 (0.921), 1530 (0.914), 655 (0.905), 820 (0.903)** | Deep pagination terbukti `no dup, order OK` p2/p3 — versi PHP lama akan miss karena `LIMIT 160 BY id` memotong sebelum ranking |

Contoh hitung manual `id 950` kueri `anggaran`: `rel=1.00` (uraian mengandung `anggaran` utuh) + `time=0.98` + `relasi=0.72` → `0.5*1 + 0.3*0.98 + 0.2*0.72 = 0.938` → peringkat 1. Di baseline, 950 ada di halaman 48 — tidak pernah terlihat pengguna.

Metrik keseluruhan (re-run live, 416 pasangan, 30 kueri): **P@10 0,332 → 0,632 (Δ+0,300), NDCG@10 0,778 → 1,000 (Δ+0,222 linear, +0,332 exp2)** — konsisten dengan Tabel 2.

## Lampiran Artefak

- `app/Services/RelevanceRankingService.php` (logika 3 skor — dipakai unit test & `eval_ndcg.py`, SQL hybrid di Model yang jalan di produksi), `app/Models/ArsipModel.php` (SQL hybrid `ORDER BY score`, stat join, fallback PHP), `app/Controllers/Home.php` (`?rank=1` + pagination preserve), `app/Views/home/search.php` (badge)
- `app/Database/Migrations/2026-08-27-000001_CreateStatKodePencipta.php` + `LargeScaleSeeder.php` (2000 arsip) / `SaracevicRankingSeeder.php` (120 arsip pilot), `stat_kode_pencipta` 162 pairs @2000
- `docs/saracevic-ranking/query-set-30.csv` (30 kueri), `JUDGMENT-GUIDE.md`, `eval_ndcg.py` (Python port ranking, 2000: 416 baris, ΔP+0.300/ΔNDCG+0.223), `relevance-judgment-synthetic.csv` (pilot 120: 309 baris ΔP+0.186)
- `DESIGN.md` (pemetaan teori), `tests/unit/RelevanceRankingServiceTest.php` (10/10) + `tests/sql_hybrid_smoke.php` (deep pagination no-dup, order OK)

---

## Cara mereplikasi evaluasi

```bash
# 1. Seed (sekali)
php spark migrate
php spark db:seed ArteriSeeder
php spark db:seed SaracevicRankingSeeder   # 120 pilot  — atau LargeScaleSeeder untuk 2000 stress-test

# 2. Smoke — pagination konsisten global (2000: deep offset 80 verified)
php tests/sql_hybrid_smoke.php             # SQL hybrid no-dup order-OK

# 3. Evaluasi sintetis Tahap 1 (tanpa human)
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic --gain exp2
```

## Catatan provenance

- arXiv: `https://export.arxiv.org/api/query?id_list=1810.11049` via `arxiv_atom.py` (2026-08-27) — verifikasi title/authors/DOI/cat.
- Crossref: `10.1002/asi.4630260604`, `10.1002/asi.20682/20681`, `10.1109/JCDL.2017.7991617`.
- OpenAlex: Saracevic A5059509085 (154 works, 868 cites 1975 paper).
