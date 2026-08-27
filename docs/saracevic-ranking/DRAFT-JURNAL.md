# Pengembangan Sistem Temu Kembali Arsip Digital Berbasis Model Relevansi Bertingkat Saracevic: Implementasi pada Arteri-2

> **Draft jurnal — DSR (Design Science Research)** | Branch: `feat/saracevic-relevance-ranking` | Target: *Archival Science / Records Management Journal / Government Information Quarterly*
> **Kata kunci:** temu kembali arsip, relevansi bertingkat, Saracevic, stratified model, ranking, Arteri, Fafalios

---

## Abstrak (250 kata)

Temu kembali arsip digital di lingkungan pemerintah masih didominasi pencocokan kata kunci tanpa pemeringkatan. Setiap arsip yang mengandung kata kunci dianggap sama relevan, padahal kebutuhan pengguna bersifat situasional—jatuh tempo retensi, hak akses klasifikasi, dan tugas yang sedang dikerjakan. Penelitian Design Science Research ini mengembangkan **sistem temu kembali arsip berbasis model relevansi bertingkat Saracevic (1975; 2007) dan Stratified Model (1997)**, diadaptasi dari **Fafalios et al. (2017, arXiv:1810.11049)**. Implementasi pada **Arteri-2** (CodeIgniter 4, MySQL/SQLite, mengelola kode/retensi/pencipta/pengolah/lokasi/media) memetakan tiga komponen Fafalios ke strata Saracevic: (i) *kecocokan kata* → relevansi sistem & topikal, (ii) *urgensi waktu* (`b=tanggal+retensi`) → situasional, (iii) *kedekatan entitas* (`kode:pencipta`) → kognitif, dengan **skor = 0,5×kecocokan + 0,3×urgensi + 0,2×kedekatan**. Rumus dievaluasi langsung di query SQL sebagai `ORDER BY score` sehingga pagination konsisten global (page 2 = peringkat 21–40, bukan 160 pertama BY id). Evaluasi sintetis 120 arsip / 30 kueri: **ΔP@10 +0,186, ΔNDCG@10 +0,172**; pada 2000 arsip **ΔP@10 +0,300, ΔNDCG@10 +0,223**. Prototipe menyediakan mode A/B `?rank=0/1` untuk studi human judgment 5 level Saracevic. Kontribusi: operasionalisasi pertama relevansi bertingkat untuk kearsipan pemerintah Indonesia dengan artefak terbuka yang dapat direplikasi.

---

## 1. Pendahuluan

### 1.1 Latar belakang
Indonesia mewajibkan pengelolaan arsip sesuai klasifikasi dan jadwal retensi (JRA). Namun, sistem temu kembali arsip di banyak instansi masih mengandalkan `LIKE '%keyword%'` tanpa ranking. Studi Fafalios dkk. pada arsip koran menunjukkan bahwa *semantic layers* (RDF/SPARQL) pun menghadapi masalah yang sama: semua hasil yang memenuhi query dianggap sama, sehingga pengguna dibanjiri dokumen.

### 1.2 Kesenjangan
Literatur kearsipan lebih banyak membahas preservasi dan klasifikasi, bukan **relevansi** sebagai konstruk bertingkat. Teori relevansi Saracevic telah mapan di ilmu informasi/perpustakaan, tetapi belum dioperasionalkan untuk arsip pemerintah yang memiliki dimensi unik: retensi sebagai *timeliness* situasional dan batas akses klasifikasi sebagai *cognitive relevance*.

### 1.3 Tujuan & pertanyaan penelitian
- **RQ1.** Bagaimana memetakan model ranking Fafalios (relativeness–timeliness–relations) ke strata Saracevic untuk domain arsip?
- **RQ2.** Sejauh mana ranking bertingkat meningkatkan efektivitas temu kembali dibanding baseline tanpa ranking (diukur P@10 & NDCG@10)?
- **RQ3.** Bagaimana merancang rubrik evaluasi 5 level Saracevic yang dapat dipakai asesor kearsipan?

### 1.4 Kontribusi
Artefak: `RelevanceRankingService`, `ArsipModel::searchRanked`, dataset 120 arsip & 30 kueri, dan skrip evaluasi terbuka—siap direplikasi di instansi lain.

## 2. Tinjauan Pustaka

### 2.1 Relevansi bertingkat Saracevic
Saracevic (1975) memperkenalkan 5 level relevansi: *system, topical, cognitive, situational, motivational*, diperbarui pada 2007 (Part II & III, *JASIST*). Relevansi bukan biner, melainkan berlapis sesuai konteks pengguna.

### 2.2 Stratified Model of IR Interaction (1996–1997)
Model berstrata: *surface ↔ cognitive ↔ affective ↔ situational*. Tiap strata dapat diukur terpisah; relevan untuk merancang komponen ranking yang dapat diuji secara independen.

### 2.3 Evaluasi digital library (Saracevic 2000) & Fafalios et al. (2017)
Saracevic mengusulkan evaluasi 5 dimensi (construct, system-centered, human-centered, use-centered, social). Fafalios dkk. mengusulkan ranking 3 komponen untuk *semantic layers* di atas arsip digital; evaluasi dengan NDCG menunjukkan peningkatan. Penelitian ini mengadaptasi Fafalios ke basis MySQL/SQLite Arteri dan mengikatnya ke strata Saracevic.

### 2.4 Posisi penelitian
Belum ada studi yang mengintegrasikan ketiganya untuk arsip pemerintah Indonesia dengan retensi sebagai sinyal *timeliness*.

## 3. Metode (Design Science Research)

**Siklus 1 — Problem & desain:** analisis `ArsipModel::buildSearchQuery` (tanpa ranking, `ORDER BY a.id`). Pemetaan Fafalios→Saracevic→field Arteri (Tabel 1).

**Siklus 2 — Build:** `RelevanceRankingService` (logika 3 skor, dipakai untuk unit test & `eval_ndcg.py`) + `ArsipModel::searchRanked` **SQL hybrid** (`ORDER BY score` di DB → pagination konsisten global, driver-aware MySQL/SQLite untuk `b=tanggal+retensi`) + `stat_kode_pencipta` (162 pairs @2000) + toggle `?rank=1` di `Home::search` (pagination preserve `?katakunci&rank=1`).

**Siklus 3 — Evaluasi:** 120 arsip sintetis (tahun 2018–2026, retensi 1/3/5/10) dan 30 kueri (Tabel query-set-30.csv). Tahap awal evaluasi sintetis (gain = round(score×3)); tahap lanjutan human judgment 5 level dengan `eval_ndcg.py` (P@10, NDCG@10, gain linear/exp2).

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

**3. Nilai Kedekatan Entitas — *relations* (bobot 20%)**
> *Apakah kombinasi “kode + pencipta” di arsip ini memang sering bekerja bersama?*

Kami hitung statistik: pasangan `kode:pencipta` mana yang paling sering muncul di 2000 arsip (tabel `stat_kode_pencipta`). Mis. `SDM.01 : Bidang Kepegawaian` muncul 28× (maksimum), maka pasangan itu skor **1.00**. Pasangan yang muncul 14× skor **0.50**. Tidak pernah → **0.00**. Ini menangkap “kebiasaan” organisasi tanpa perlu AI mahal.

*Padanan Saracevic:* Relevansi Kognitif — apakah arsip ini nyambung dengan pengetahuan/struktur organisasi yang dikenal pengguna?

**Skor akhir — seperti rapor gabungan:**
```
skor = 0.5 × kecocokan + 0.3 × urgensi + 0.2 × kedekatan      ∈ [0, 1]
# 0.5+0.3+0.2 = 1, jadi skor tetap 0–1 (0.87 = 87%). Diurutkan besar→kecil.
```
Contoh nyata @2000 arsip (`anggaran`): `SDM.01:Kepegawaian` dengan `rel=1.00, time=0.98, relasi=0.72` → `0.5*1 +0.3*0.98+0.2*0.72 = 0.938` → peringkat 1. Arsip lain `rel=1, time=0.20, relasi=0` → 0.56 → peringkat bawah, walau kata cocok tapi tidak urgent dan tidak nyambung.

**Kenapa rumus sekarang di query SQL (hybrid)?**
Versi lama: ambil 160 arsip pertama BY id → hitung skor di PHP → potong. Kalau arsip paling relevan ada di id 900, ia **tidak pernah terambil** → page 2 jadi salah. Versi sekarang: **hitung skor di dalam SQL sebagai `ORDER BY score`** — database mengurutkan *semua* arsip dulu baru memotong `LIMIT 20 OFFSET 40` → **page 2 = peringkat 21–40 global, konsisten**. Ini penting untuk klaim ilmiah di paper.

**A/B untuk evaluasi:** `/search?katakunci=anggaran` (baseline `ORDER BY id`) vs `/search?katakunci=anggaran&rank=1` (SQL hybrid `ORDER BY score`); badge *Ranked (Saracevic)* di view. Link pagination sekarang `?katakunci=...&rank=1` terbawa ke halaman 2/3.

## 5. Hasil Awal (Sintetis)

**Skala 120 arsip (baseline jurnal — 30 kueri × 10 teratas, 309 pasangan, gain sintetis dari skor):** P@10 **0,321 → 0,507 (Δ+0,186)**; NDCG@10 **0,828 → 1,000 (Δ+0,172)**. Skala kecil untuk mengisolasi efek ranking tanpa noise.

**Skala 2000 arsip (uji performa & generalisasi — 30 kueri × 10 teratas, 416 pasangan):** P@10 **0,332 → 0,632 (Δ+0,300)**; NDCG@10 **0,777 → 1,000 (Δ+0,223)** — gain membesar karena ranking global SQL mengangkat arsip relevan yang di versi lama tersembunyi di luar 160 pertama (terutama kueri umum seperti `arsip` 560 hit). Detail per-kueri ada di `relevance-judgment-synthetic.csv`; `eval_ndcg.py` mereplikasi.

| Skala | ΔP@10 | ΔNDCG@10 | Makna |
|-------|-------|----------|-------|
| 120 | +0,186 | +0,172 | Efek ranking murni (tanpa-pagination bug) |
| 2000 | +0,300 | +0,223 | **Efek ranking + perbaikan pagination global** — relevan untuk klaim “skalabel” |

> **Catatan untuk jurnal:** hasil sintetis bersifat demonstrasi (gain = round(score×3)); klaim final menunggu *human judgment* 5 level (JUDGMENT-GUIDE.md, 2 asesor, κ Cohen). NDCG=1,000 pada sintetis merefleksikan ideal yang dibangun dari gain yang sama — bedakan dari NDCG human. Laporkan keduanya (120 & 2000) di paper: 120 sebagai *pilot*, 2000 sebagai *stress-test*.

## 6. Pembahasan

**Implikasi teori:** retensi sebagai *timeliness* adalah wujud *situational relevance* yang khas arsip; batas akses klasifikasi adalah *cognitive relevance* yang perlu dipisah sebagai filter, bukan bobot.

**Implikasi praktis:** mode A/B memungkinkan instansi membandingkan tanpa mengubah perilaku default (rank opsional).

**Keterbatasan:** gain sintetis bias oleh model; belum ada personalisasi; ko-okurensi masih dari himpunan hasil, belum dari KG eksternal.

## 7. Simpulan & Penelitian Lanjutan

Relevansi bertingkat Saracevic dapat dioperasionalkan sebagai ranking 3 komponen yang bermakna untuk arsip. Evaluasi human judgment dan tuning bobot (mis. pembelajaran bobot dari log) menjadi langkah berikutnya.

---

## Daftar Pustaka (inti — terverifikasi 2026-08-27)

- Saracevic, T. (1975). RELEVANCE: A review of and a framework for the thinking on the notion in information science. *Journal of the American Society for Information Science*, 26(6), 321–343. https://doi.org/10.1002/asi.4630260604 — 868 sitasi OpenAlex.
- Saracevic, T. (1997). The Stratified Model of Information Retrieval Interaction: Extension and Applications. *Proceedings of the ASIST Annual Meeting*, 34, 313–327. — 155 sitasi.
- Saracevic, T. (2007). Relevance: A review of the literature and a framework for thinking on the notion in information science. Part II: Nature and manifestations of relevance. *JASIST*, 58(13), 1915–1933. https://doi.org/10.1002/asi.20682
- Saracevic, T. (2007). Relevance ... Part III: Behavior and effects of relevance. *JASIST*, 58(14), 2126–2144. https://doi.org/10.1002/asi.20681
- Fafalios, P., Kasturia, V., & Nejdl, W. (2017). Towards a Ranking Model for Semantic Layers over Digital Archives. *Proc. JCDL 2017*. https://doi.org/10.1109/JCDL.2017.7991617 — arXiv:1810.11049 [cs.IR, cs.DL].
- Faggioli, G., Dietz, L., & Clarke, C. L. A. (2023). Perspectives on Large Language Models for Relevance Judgment. arXiv:2304.09161. https://doi.org/10.1145/3578337.3605136 — untuk diskusi keterbatasan LLM-as-judge.

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
php spark db:seed SaracevicRankingSeeder   # 120 arsip

# 2. Smoke ranking
php tests/rank_smoke_pdo.php               # cek reorder & b/f

# 3. Evaluasi sintetis
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic
python3 docs/saracevic-ranking/eval_ndcg.py --synthetic --gain exp2

# 4. Evaluasi human judgment (setelah isi CSV)
python3 docs/saracevic-ranking/eval_ndcg.py docs/saracevic-ranking/relevance-judgment.csv
```

## Catatan provenance

- arXiv: `https://export.arxiv.org/api/query?id_list=1810.11049` via `arxiv_atom.py` (2026-08-27) — verifikasi title/authors/DOI/cat.
- Crossref: `10.1002/asi.4630260604`, `10.1002/asi.20682/20681`, `10.1109/JCDL.2017.7991617`.
- OpenAlex: Saracevic A5059509085 (154 works, 868 cites 1975 paper).
