# Relevance Judgment — Saracevic 5 Level (Arteri-2)

## Skala per level (0-3)
- 0 = tidak relevan pada level ini
- 1 = agak relevan
- 2 = relevan
- 3 = sangat relevan

## Level Saracevic (1975/2007) — isikan 0-3 untuk tiap level:
1. **System**    — keyword cocok secara sistem (LIKE match)
2. **Topical**   — topik sesuai kebutuhan (mis. “rekrutmen” memang tentang rekrutmen)
3. **Cognitive** — sesuai pengetahuan/tugas pencari (kode & pencipta yang ia pahami)
4. **Situational** — berguna untuk tugas saat ini (mis. “sudah jatuh tempo” membantu pemusnahan)
5. **Motivational** — mendorong tindakan (mis. regulasi yang jadi dasar keputusan)

## overall_relevance_0_3
- 0 tidak relevan, 1 marginal, 2 relevan, 3 sangat relevan (untuk NDCG)

## Prosedur (30 query × 10 arsip = 300 penilaian per assessor)
1. Jalankan baseline: /search?katakunci=<keywords>
2. Jalankan ranked:   /search?katakunci=<keywords>&rank=1
3. Ambil 10 teratas tiap mode, catat rank_baseline & rank_saracevic (1-10, kosong jika tidak muncul di top-10 mode tersebut)
4. Isi 5 level + overall
5. Minimal 2 assessor → hitung Cohen's kappa; overall dipakai untuk Precision@10 & NDCG@10

## File
- query-set-30.csv — daftar query
- relevance-judgment-template.csv — template penilaian (duplikasi baris per arsip yang dinilai)
- eval-metrics.sql — helper hitung P@10/NDCG (opsional)

Contoh baris terisi:
Q01,42,3,1,3,3,2,2,1,3,"Topical & System kuat, Situational membantu karena retensi belum lewat"
