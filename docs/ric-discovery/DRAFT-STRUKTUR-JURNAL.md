# Draft Kerangka Artikel Jurnal 2 (Design Science Research)

## Judul Usulan
**Pemodelan Penelusuran Kontekstual Arsip Digital Berbasis Standar Records in Contexts (RiC-CM): Implementasi dan Evaluasi pada Sistem Arteri-2**

*(Modeling Contextual Discovery of Digital Records Based on the Records in Contexts Conceptual Model: Implementation and Evaluation in Arteri-2)*

---

## Target Publikasi
* *Archival Science* (Springer) / *Records Management Journal* (Emerald) / *Journal of Documentation*

---

## 1. Pendahuluan
* **1.1 Latar Belakang:**
  Perkembangan standar deskripsi kearsipan dari model hierarkis kaku (ISAD(G)) menuju graf relasional multidimensi (*Records in Contexts* / RiC-CM v1.0 oleh ICA).
* **1.2 Kesenjangan Penelitian (*Research Gap*):**
  Banyak sistem informasi kearsipan operasional masih mengadopsi model relasional terisolasi (dokumen dicari berdasarkan kata kunci teks mentah). Ketiadaan implementasi komputasi praktis dari RiC-CM yang menjembatani ontologi kearsipan tingkat tinggi ke sistem web relasional (*RDBMS-backed systems*).
* **1.3 Pertanyaan Penelitian (*Research Questions*):**
  * **RQ1:** Bagaimana memetakan entitas dan relasi standar RiC-CM v1.0 ke dalam skema basis data relasional pada sistem manajemen arsip dinamis?
  * **RQ2:** Bagaimana formulasi algoritma penelusuran afinitas kontekstual (*Contextual Affinity Score*) untuk merekonstruksi jejaring berkas (*dossier discovery*) lintas entitas?
  * **RQ3:** Sejauh mana implementasi modul RiC mampu meningkatkan akurasi penemuan berkas terkait (*linked records precision/recall*) dibandingkan metode penelusuran relasional konvensional?

---

## 2. Landasan Teori
* **2.1 Standar Records in Contexts (RiC-CM & RiC-O):**
  Prinsip 4 entitas inti (`RecordResource`, `Agent`, `Activity`, `Place`) dan relasi multidimensi.
* **2.2 Rekonstruksi Provenans Digital (*Digital Provenance Networks*):**
  Pentingnya rantai konteks fungsional dan kelembagaan dalam menjaga integritas serta keotentikan arsip.
* **2.3 Contextual Information Retrieval & Graph Traversal:**
  Konsep temu kembali informasi berbasis konteks dan kedekatan graf (*graph proximity*).

---

## 3. Metodologi Penelitian (DSR Framework - Peffers et al., 2007)
* **Tahap 1 — Problem Identification:** Analisis isolasi rekaman pada basis data relasional kearsipan.
* **Tahap 2 — Solution Objectives:** Definisi kebutuhan integrasi RiC-CM tanpa merombak total struktur RDBMS.
* **Tahap 3 — Artifact Design & Development:**
  * Perancangan `RicContextualDiscoveryService` pada Arteri-2 (CodeIgniter 4).
  * Formulasi formula skor afinitas kontekstual ($CAS$).
  * Pembangkitan representasi semantik JSON-LD RiC-O.
* **Tahap 4 — Demonstration & Technical Evaluation:**
  * Pengujian temu kembali berkas terkait pada skenario multi-unit dan multi-klasifikasi.
  * Evaluasi metrik efektivitas penemuan relasi (*Cluster Coherence, Precision@K of Related Dossiers*).

---

## 4. Perancangan dan Implementasi Artefak
*(Penjelasan mendalam mengenai arsitektur kode, formula matematis $CAS$, dan serialisasi JSON-LD)*

---

## 5. Evaluasi dan Pembahasan
*(Hasil pengujian benchmark komparatif terhadap metode pencarian konvensional vs RiC contextual discovery)*

---

## 6. Kesimpulan dan Agenda Riset Lanjutan
