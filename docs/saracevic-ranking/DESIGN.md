# Pengembangan Sistem Temu Kembali Arsip Berbasis Saracevic — Implementasi Arteri-2

> Branch: `feat/saracevic-relevance-ranking` | Tanggal: 2026-08-27 | Status: DESIGN

## 1. Paper acuan arXiv (deep-dive)

**Fafalios, P., Kasturia, V., & Nejdl, W. (2018). Towards a Ranking Model for Semantic Layers over Digital Archives. arXiv:1810.11049 [cs.IR, cs.DL]. DOI: 10.1109/JCDL.2017.7991617 — *2017 ACM/IEEE Joint Conference on Digital Libraries (JCDL).***

- **Masalah:** Semantic Layers (RDF/SPARQL) di atas arsip digital (contoh: newspaper archive) bisa jawab query terstruktur (metadata + entitas), tapi semua hasil **equally match** — tidak ada ranking. User dibanjiri hasil.
- **Solusi paper:** Model ranking 3 komponen yang dikombinasikan:
  1. **Relativeness of documents to entities** — seberapa terkait dokumen dengan entitas yang ditanya (diukur via frekuensi/konteks kemunculan entitas dalam dokumen, mirip TF-IDF untuk entity).
  2. **Timeliness of documents** — relevansi temporal: dokumen yang *timely* (dekat dengan peristiwa/entitas pada waktunya) diberi bobot lebih.
  3. **Relations among entities** — kalau query berisi >1 entitas, dokumen yang memuat entitas yang saling berhubungan (co-occurrence / knowledge graph) diberi boost.
- **Evaluasi:** Uji pada newspaper archive dengan SPARQL + human judgment, ranking vs baseline tanpa ranking → NDCG meningkat.
- **Keterbatasan paper:** Entitas diekstrak via NER; tidak ada personalisasi & tidak ada dimensi situasional tugas pengguna.

> **Provenance (verifikasi 2026-08-27):**
> - arXiv Atom `https://export.arxiv.org/api/query?id_list=1810.11049` via `scripts/arxiv_atom.py` → title/authors/abstract/DOI terverifikasi.
> - Crossref `https://api.crossref.org/works/10.1109/JCDL.2017.7991617` → JCDL 2017, is-referenced-by-count 1 (perlu kutip sebagai conference, bukan journal).
> - Kategori `cs.IR` + `cs.DL` → cocok untuk arsip digital.

## 2. Teori Saracevic yang dipakai (primer — bukan arXiv)

| Teori | Sumber primer (verifikasi Crossref/OpenAlex 2026-08-27) | Peran di Arteri-2 |
|-------|----------------------------------------------------------|-------------------|
| **Relevansi Bertingkat (5 level)** | Saracevic (1975) DOI 10.1002/asi.4630260604 (868 cites) → update 2007 Part II DOI 10.1002/asi.20682 + Part III DOI 10.1002/asi.20681 | Menjadi **kerangka evaluasi**: System → Topical → Cognitive → Situational → Motivational. Fafalios hanya cover System/Topical; kita naikkan ke Situational. |
| **Stratified Model of IR Interaction** | Saracevic (1997) *The Stratified Model...* Proc. ASIST (155 cites) — versi 1996 *Modeling Interaction in IR* (151 cites) | Menjadi **arsitektur sistem**: Surface (UI/keyword) ↔ Cognitive (klasifikasi & pengetahuan user) ↔ Affective ↔ Situational (tugas & retensi). Tiap strata = komponen ranking terpisah yang bisa diuji. |
| **Digital Library Evaluation** | Saracevic (2000) *Digital Library Evaluation* (229 cites) | Menjadi **rubrik evaluasi jurnal** (system-centered vs user-centered). |

## 3. Pemetaan ke Arteri-2 (gap analysis)

**Kondisi sekarang (`ArsipModel::buildSearchQuery`):**
- `LIKE noarsip/uraian/nobox` (OR) + filter advanced (AND) + filter `akses_klas` (prefix LIKE `k.kode`).
- Urutan: `ORDER BY a.id ASC` — **tidak ada ranking**, semua match dianggap sama → persis masalah Fafalios.
- Field tersedia: `noarsip, uraian, tanggal, kode(id→kode.retensi), pencipta, unit_pengolah, lokasi, media, ket, nobox`, derived `b = DATE_ADD(tanggal, INTERVAL retensi YEAR)` (jatuh tempo), `f = sudah/belum`.

**Adaptasi Fafalios → Saracevic → Arteri:**

| Komponen Fafalios | Padanan Arteri-2 | Level Saracevic | Implementasi (SQL/PHP) |
|-------------------|------------------|-----------------|------------------------|
| **Relativeness** | Skor kecocokan query ke `uraian` + `noarsip` + nama master (pencipta/pengolah/kode). Field `uraian` adalah *content*, `kode` adalah *entity* klasifikasi. | System + Topical | `CASE WHEN uraian LIKE '%keyword%' THEN 3 WHEN noarsip LIKE '%kw%' THEN 2 ELSE 0 END` + bonus `LIKE nama_pencipta / nama_pengolah` via JOIN. Normalisasi 0-1. |
| **Timeliness** | Urgensi retensi: `b` (expiry date). Arsip yang mendekati/lewat retensi lebih *timely* untuk tugas pemusnahan/penyerahan. | Situational | `timeliness = 1 / (1 + |DATEDIFF(b, CURDATE())|/365)` — nilai 1 saat jatuh tempo hari ini, meluruh seiring jarak. Arsip `f='sudah'` dapat boost jika user mencari retensi=sudah. |
| **Relations among entities** | Ko-okurensi `kode ↔ pencipta ↔ pengolah`. Misal query `SDM.01 + pencipta X` → arsip yang sering muncul bersama di histori diberi boost (stat `COUNT GROUP BY kode,pencipta`). Bisa precompute tabel `stat_kode_pencipta`. | Cognitive + Situational | `relation = cooccurrence(kode, pencipta) / max_cooccurrence` — lookup tabel statistik (tanpa KG eksternal). Fallback 0 jika tidak ada. |
| **+ Hak akses (baru, khas Saracevic Stratified)** | `akses_klas` user — bukan ranking, tapi *filter kognitif*: hanya klas yang boleh dilihat. Dipisah dari skor. | Cognitive | Tetap filter `WHERE k.kode LIKE prefix%` sebelum ranking (seperti sekarang). |

**Skor akhir (Fafalios §3, weighted sum — sekarang di SQL sebagai ORDER BY score, bukan PHP):**
```
skor = 0.5 × kecocokan + 0.3 × urgensi + 0.2 × kedekatan   ∈ [0,1] — pagination konsisten global
```
Penjelasan awam ada di DRAFT-JURNAL.md §4.1; SQL di ArsipModel::searchRanked (driver-aware MySQL/SQLite). Versi PHP (RelevanceRankingService) tetap dipertahankan untuk unit test & eval_ndcg.py.

**Update 2026-08-27 (SQL hybrid):** `searchRanked` sekarang `SELECT ... (score) as score ORDER BY score DESC LIMIT/OFFSET` + `stat_kode_pencipta` (162 pairs @2000). Pagination `?rank=1` preserve via `setPath('search?...&rank=1')`.

## 4. Rencana implementasi di branch ini

### Fase 1 — Service & Model (MVP, tanpa ubah UI)
- [x] `app/Services/RelevanceRankingService.php` — hitung 3 komponen (tetap untuk test), `rank(array $rows, string $keywords): array`
- [x] `app/Models/ArsipModel.php` — SQL hybrid `searchRanked` (ORDER BY score, stat_kode_pencipta)(string $keywords, array $filters, int $limit, int $offset, array $weights): array` dan `searchRankedCount` — panggil service setelah `buildSearchQuery`.
- [x] `app/Database/Migrations/2026-08-27-000001_CreateStatKodePencipta.php` + `LargeScaleSeeder` (2000)
- [x] `app/Controllers/Home.php::search()` — `?rank=1` + pagination preserve untuk bandingkan ranking lama vs baru (A/B untuk jurnal).
- [x] Unit test `tests/unit/RelevanceRankingServiceTest.php` 10/10 + `tests/sql_hybrid_smoke.php` — 3 komponen + skor gabungan.

### Fase 2 — Evaluasi untuk jurnal (butuh data)
- Seed 100-200 arsip dummy variasi retensi & entitas.
- Kumpulkan 30 query + relevance judgment 5 level Saracevic (buat spreadsheet).
- Hitung Precision@10, NDCG per level.

### Fase 3 — UI (opsional)
- Badge skor & penjelasan "Kenapa arsip ini di atas?" (explainable ranking — nilai jual jurnal).

## 5. Daftar pustaka inti (APA 7th — semua terverifikasi)

- Saracevic, T. (1975). RELEVANCE: A review of and a framework for the thinking on the notion in information science. *Journal of the American Society for Information Science*, *26*(6), 321–343. https://doi.org/10.1002/asi.4630260604
- Saracevic, T. (1997). The Stratified Model of Information Retrieval Interaction: Extension and Applications. *Proceedings of the ASIST Annual Meeting*, 34, 313–327.
- Saracevic, T. (2007). Relevance: A review of the literature and a framework for thinking on the notion in information science. Part II: Nature and manifestations of relevance. *JASIST*, *58*(13), 1915–1933. https://doi.org/10.1002/asi.20682
- Saracevic, T. (2007). Relevance: A review... Part III: Behavior and effects of relevance. *JASIST*, *58*(14), 2126–2144. https://doi.org/10.1002/asi.20681
- Fafalios, P., Kasturia, V., & Nejdl, W. (2017). Towards a Ranking Model for Semantic Layers over Digital Archives. *Proc. JCDL 2017*. https://doi.org/10.1109/JCDL.2017.7991617 — arXiv:1810.11049
- Faggioli, G., Dietz, L., & Clarke, C. L. A. (2023). Perspectives on Large Language Models for Relevance Judgment. arXiv:2304.09161. https://doi.org/10.1145/3578337.3605136 (untuk diskusi limitasi LLM judge)

## 6. Keputusan desain
- Tidak pakai RDF/SPARQL (Arteri pakai MySQL) — adaptasi jadi weighted SQL + PHP, tetap setia pada ide Fafalios.
- Stratified dipakai sebagai *lensa evaluasi*, bukan kode per strata — supaya jurnal bisa klaim kontribusi teori.
- Judul kerja jurnal: **Pengembangan Sistem Temu Kembali Arsip Digital Berbasis Model Relevansi Bertingkat Saracevic: Implementasi pada Arteri Kemenhub (Arteri-2)**

---
*File ini adalah living doc — update tiap fase.*
