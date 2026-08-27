# Pengembangan Sistem Temu Kembali Arsip Digital Berbasis Model Relevansi Bertingkat Saracevic: Implementasi pada Arteri-2

> **Draft jurnal — DSR (Design Science Research)** | Branch: `feat/saracevic-relevance-ranking` | Target: *Archival Science / Records Management Journal / Government Information Quarterly*
> **Kata kunci:** temu kembali arsip, relevansi bertingkat, Saracevic, stratified model, ranking, Arteri, Fafalios

---

## Abstrak (250 kata)

Temu kembali arsip digital di lingkungan pemerintah masih didominasi pencocokan kata kunci (keyword matching) tanpa pemeringkatan relevansi. Akibatnya, setiap arsip yang mengandung kata kunci dianggap sama relevan, padahal kebutuhan pengguna bersifat situasional—terkait jatuh tempo retensi, hak akses klasifikasi, dan tugas yang sedang dikerjakan. Penelitian ini mengembangkan sistem temu kembali arsip berbasis **model relevansi bertingkat Saracevic (1975; 2007)** dan **Stratified Model of IR Interaction (1997)** yang diadaptasi dari model ranking **Fafalios et al. (2017, arXiv:1810.11049, JCDL)** untuk arsip digital. Implementasi dilakukan pada **Arteri-2**, sistem kearsipan berbasis CodeIgniter 4 yang mengelola kode klasifikasi, retensi, pencipta, pengolah, lokasi, dan media. Dari model Fafalios (relativeness–timeliness–relations) diturunkan tiga komponen yang dipetakan ke strata Saracevic: (i) *relativeness* → relevansi sistem & topikal, (ii) *timeliness* → relevansi situasional berbasis `tanggal + retensi`, dan (iii) *relations* → relevansi kognitif melalui ko-okurensi `kode↔pencipta`. Skor akhir `w1·rel + w2·time + w3·relasi` diuji pada 120 arsip sintetis dan 30 kueri berlabel level Saracevic. Evaluasi sintetis (skor→gain 0–3) menunjukkan **ΔP@10 +0,186 dan ΔNDCG@10 +0,172** dibanding baseline `ORDER BY id`. Prototipe menyediakan mode A/B `?rank=0/1` untuk studi human judgment lanjutan dengan rubrik 5 level Saracevic. Kontribusi: operasionalisasi pertama relevansi bertingkat Saracevic untuk kearsipan pemerintah Indonesia dengan artefak yang dapat direplikasi.

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

**Siklus 2 — Build:** implementasi `RelevanceRankingService` (tokenize, skor relativeness/timeliness/relations) dan `searchRanked` (fetch 500 max → rank global → slice). Dukungan MySQL & SQLite untuk `b = tanggal + retensi` dan `f = sudah/belum`. Toggle `?rank=1` di `Home::search`.

**Siklus 3 — Evaluasi:** 120 arsip sintetis (tahun 2018–2026, retensi 1/3/5/10) dan 30 kueri (Tabel query-set-30.csv). Tahap awal evaluasi sintetis (gain = round(score×3)); tahap lanjutan human judgment 5 level dengan `eval_ndcg.py` (P@10, NDCG@10, gain linear/exp2).

## 4. Implementasi pada Arteri-2

**Arsitektur:** CodeIgniter 4, MySQL/SQLite, `data_arsip` + 5 master. Perubahan: `app/Services/RelevanceRankingService.php`, `app/Models/ArsipModel.php`, `app/Controllers/Home.php`, `app/Views/home/search.php`.

**Rumus:**
```
relativeness = max-field-match(tokens) / (3·|tokens|)   ∈ [0,1]
timeliness   = 1/(1+|b−today|/365) (+0.08 jika sudah)    ∈ [0,1]
relations    = count(kode:pencipta)/maxCount             ∈ [0,1]
score        = 0.5·rel + 0.3·time + 0.2·relasi             ∈ [0,1]
```

**A/B:** `/search?katakunci=…` vs `/search?katakunci=…&rank=1`; badge *Ranked (Saracevic)* di view.

## 5. Hasil Awal (Sintetis)

30 kueri × 10 teratas, 309 pasangan query–arsip, gain sintetis dari skor. **Rata-rata sintetis: P@10 baseline 0,321 → ranked 0,507 (Δ+0,186); NDCG@10 0,828 → 1,000 (Δ+0,172).** Peningkatan terbesar pada kueri situasional (retensi, multi-kode) dan multi-token (Q16, Q24, Q25). Detail per-kueri ada di `relevance-judgment-synthetic.csv`; skrip `eval_ndcg.py` mereplikasi perhitungan.

> **Catatan untuk jurnal:** hasil sintetis bersifat demonstrasi; klaim final menunggu *human judgment* dengan rubrik JUDGMENT-GUIDE.md (2 asesor, κ Cohen). Nilai NDCG=1,000 pada sintetis merefleksikan ideal yang dibangun dari gain yang sama dengan ranking—pembaca jurnal perlu membedakan dari NDCG human.

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

- `app/Services/RelevanceRankingService.php`, `app/Models/ArsipModel.php` (driver-aware), `app/Controllers/Home.php` (`?rank=1`), `app/Views/home/search.php`
- `app/Database/Seeds/SaracevicRankingSeeder.php` (120 arsip), `docs/saracevic-ranking/query-set-30.csv`, `JUDGMENT-GUIDE.md`, `eval_ndcg.py`, `relevance-judgment-synthetic.csv` (309 baris, ΔP+0,186/ΔNDCG+0,172)
- `DESIGN.md` (pemetaan teori), `tests/unit/RelevanceRankingServiceTest.php` (10/10)

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
