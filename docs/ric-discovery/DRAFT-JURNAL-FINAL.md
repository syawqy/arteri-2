# Pemodelan Penelusuran Kontekstual Arsip Digital Berbasis Standar Records in Contexts (RiC-CM): Implementasi dan Evaluasi pada Sistem Arteri-2

> **NASKAH TAHAP 1 — Technical & Empirical Validation** | Branch `feat/ric-contextual-discovery` | 2026-09-02  
> Target Jurnal: *Archival Science / Records Management Journal / Journal of Documentation*  
> Kata Kunci: Records in Contexts, RiC-CM, RiC-O, temu kembali kontekstual, ontologi kearsipan, rekonsiliasi provenans, Arteri-2, Design Science Research

---

## Abstrak

Sistem temu kembali arsip dinamis dan inaktif pada instansi pemerintah umumnya bertumpu pada pencarian leksikal kata kunci dan pengurutan kronologis datar. Paradigma ini mengabaikan hubungan provenans multidimensi—seperti keterkaitan antara unit pencipta (*Agent*), urusan/fungsi kerja (*Activity*), dan berkas fisik/logis (*Record Resource*)—sehingga menyebabkan terputusnya konteks proses bisnis (*semantic disconnection*) saat pengguna menelusuri berkas terkait (*dossier discovery*). Penelitian ini menerapkan metodologi *Design Science Research* (DSR) empat fase (Peffers et al., 2007) untuk merancang, mengimplementasikan, dan mengevaluasi artefak mesin penelusur kontekstual (*Contextual Discovery Engine*) berbasis standar internasional *Records in Contexts Conceptual Model* (ICA RiC-CM v1.0) dan ontologi RiC-O pada sistem manajemen arsip Arteri-2 (CodeIgniter 4, PHP 8.4, MySQL/SQLite). 

Artefak yang dibangun mengoperasionalkan formula *Contextual Affinity Score* ($CAS$) yang menggabungkan tiga dimensi relasional kearsipan: (i) afinitas keagenan (*Agent Affinity*, bobot $0{,}35$) berbasis pencipta dan unit pengolah, (ii) afinitas fungsional (*Activity Affinity*, bobot $0{,}45$) berbasis hierarki kode klasifikasi urusan, dan (iii) kedekatan kurun waktu (*Temporal Proximity*, bobot $0{,}20$) dengan peluruhan eksponensial. Selain antarmuka penelusuran graf kontekstual, sistem menyediakan interoperabilitas data terbuka melalui generator semantik JSON-LD RiC-O. Evaluasi empiris benchmark terkontrol terhadap berbagai klaster proses bisnis kearsipan pemerintah (pengadaan, rekrutmen, audit, dan perencanaan) menunjukkan performa unggul dibandingkan metode kronologis konvensional: $\text{P@5} = 0{,}8870$ ($\Delta\text{P@5} = +0{,}6348$), $\text{P@10} = 0{,}5130$ ($\Delta\text{P@10} = +0{,}2522$), $\text{Recall@10} = 1{,}0000$ ($\Delta\text{Recall@10} = +0{,}5280$), dan $\text{MRR} = 1{,}0000$ ($\Delta\text{MRR} = +0{,}6554$). Hasil ini membuktikan bahwa pemodelan graf RiC-CM efektif menjembatani ontologi kearsipan modern ke dalam basis data relasional operasional tanpa memerlukan perombakan infrastruktur basis data yang masif.

---

## Bagian 1. Pendahuluan

### Bagian 1.1 Latar Belakang
Pengelolaan arsip dinamis dan inaktif di lembaga publik bertujuan menjamin ketersediaan rekaman autentik guna mendukung akuntabilitas kinerja, audit hukum, dan preservasi memori institusi. Dalam tradisi kearsipan, nilai bukti (*evidential value*) suatu arsip tidak semata-mata bertumpu pada teks dokumen secara terisolasi, melainkan pada prinsip provenans (*respect des fonds*)—yaitu pemahaman mendalam mengenai siapa yang menciptakan dokumen, dalam rangka pelaksanaan tugas/fungsi apa, dan bagaimana hubungan berkas tersebut dengan dokumen lain dalam suatu siklus transaksi kerja (Duranti, 1997; MacNeil, 2000).

Namun demikian, pada implementasi sistem informasi kearsipan konvensional di berbagai lembaga, struktur basis data relasional (*RDBMS*) kerap merepresentasikan rekaman arsip sebagai baris tabel terisolasi. Ketika pengguna membuka rincian berkas tertentu, sistem hanya menampilkan atribut individual (nomor berkas, tanggal, uraian teks, lokasi fisik) tanpa menyediakan mekanisme penelusuran jejaring kontekstual yang menghubungkan berkas tersebut ke berkas lain yang berada dalam satu rantai kegiatan yang sama.

### Bagian 1.2 Kesenjangan Penelitian
Standar deskripsi kearsipan internasional telah mengalami pergeseran paradigma fundamental. Standar generasi terdahulu seperti ISAD(G) (*General International Standard Archival Description*) dan ISAAR(CPF) menerapkan struktur hierarkis pohon yang kaku, yang sering kali kesulitan merepresentasikan relasi multi-kelembagaan yang kompleks dalam administrasi publik modern. Untuk mengatasi batasan tersebut, *International Council on Archives* (ICA) merilis standar *Records in Contexts* (RiC), yang mencakup *Conceptual Model* (RiC-CM v1.0) dan representasi ontologi formalnya (*Records in Contexts Ontology* / RiC-O) (ICA Expert Group on Archival Description, 2023; Llanes-Padrón & Pastor-Sánchez, 2017; Pitti et al., 2018).

Meskipun RiC-CM telah diakui secara luas dalam diskursus teoretis kearsipan, sebagian besar literatur yang ada berfokus pada perdebatan ontologis konseptual atau konversi metadata statis ke *Resource Description Framework* (RDF). Masih sangat terbatas kajian rekayasa perangkat lunak yang mengoperasionalkan prinsip graf relasional RiC ke dalam algoritma komputasi temu kembali pada aplikasi sistem informasi kearsipan aktif berbasis web. Kesenjangan ini menimbulkan pertanyaan praktis: bagaimana memetakan entitas graf RiC ke dalam skema basis data relasional yang telah ada, serta bagaimana merumuskan algoritma penelusuran kontekstual yang terukur secara empiris?

### Bagian 1.3 Pertanyaan Penelitian
Penelitian ini bertujuan merancang, mengimplementasikan, dan mengevaluasi artefak *Contextual Discovery Engine* berbasis RiC-CM pada sistem kearsipan Arteri-2. Pertanyaan penelitian yang diajukan adalah:
* **RQ1.** Bagaimana memetakan entitas inti dan relasi standar ICA RiC-CM v1.0 ke dalam skema relasional sistem informasi kearsipan pemerintah?
* **RQ2.** Bagaimana merumuskan model komputasi skor afinitas kontekstual (*Contextual Affinity Score*) yang memadukan dimensi agen, fungsi/aktivitas, dan temporalitas?
* **RQ3.** Sejauh mana implementasi algoritma penelusuran berbasis RiC-CM meningkatkan ketepatan penemuan berkas terkait (*Precision@K*, *Recall@K*, dan *MRR*) dibandingkan metode penelusuran konvensional?

### Bagian 1.4 Batasan Penelitian
Penelitian ini memfokuskan validasi pada ranah rekayasa artefak perangkat lunak dan evaluasi empiris terukur (*system-centered benchmark evaluation*) dengan skenario rantai proses bisnis kearsipan instansi pemerintah Indonesia (kode klasifikasi urusan kearsipan, jadwal retensi arsip, dan struktur unit pengolah/pencipta). Evaluasi persepsi kognitif pengguna akhir (*user study*) dialokasikan sebagai agenda riset lanjutan.

---

## Bagian 2. Landasan Teori

### Bagian 2.1 Standar Records in Contexts (ICA RiC-CM v1.0 & RiC-O)
RiC-CM mendefinisikan empat entitas inti kearsipan:
1. **`ric:RecordResource`**: Rekaman arsip individual (*Record*), himpunan berkas (*RecordSet*), atau bagian dari rekaman (*RecordPart*).
2. **`ric:Agent`**: Entitas yang bertanggung jawab atas penciptaan, pengolahan, atau pemeliharaan arsip, mencakup badan korporasi (*CorporateBody*), individu (*Person*), atau kelompok (*Group*).
3. **`ric:Activity`**: Tindakan, proses bisnis, atau fungsi yang dilakukan oleh agen yang memicu terciptanya arsip.
4. **`ric:Place`**: Lokasi geografis atau fisik penyimpanan arsip.

Hubungan antar-entitas tidak lagi bersifat hierarkis satu arah, melainkan berbentuk jejaring (*multi-directional graph*). Sebagai contoh, sebuah `RecordResource` terhubung ke `Agent` melalui relasi `ric:hasCreator` dan `ric:hasManagingAgent`, terhubung ke `Activity` melalui relasi `ric:hasActivity`, serta terhubung ke rekaman lain melalui relasi asosiatif `ric:isContextuallyRelatedTo`.

### Bagian 2.2 Teori Rekonstruksi Provenans dan Penelusuran Berkas Terkait
Dalam konteks penelusuran berkas (*dossier discovery*), pengguna kerap mencari rekaman arsip bukan berdasarkan kecocokan kata kunci harfiah, melainkan berdasarkan asosiasi fungsional (Duranti, 1997; MacNeil, 2000). Misalnya, audit terhadap suatu pengadaan barang menuntut penemuan dokumen Kerangka Acuan Kerja, Berita Acara Evaluasi, Kontrak, hingga Kuitansi Pembayaran—yang masing-masing memiliki deskripsi teks berbeda namun saling terikat dalam satu simpul *Activity* dan *Agent* yang sama. Penelusuran berbasis graf memungkinkan penemuan berkas-berkas tersebut secara utuh.

---

## Bagian 3. Metodologi Penelitian

Penelitian ini menggunakan kerangka kerja **Design Science Research (DSR)** mengacu pada Peffers et al. (2007) yang terdiri atas empat tahapan terstruktur:

```
+---------------------------------------------------------------------------------+
| 1. Identifikasi Masalah & Analisis Kebutuhan                                    |
|    - Keterbatasan pencarian relasional datar (baris terisolasi)                 |
|    - Putusnya rantai provenans pada penelusuran berkas terkait                  |
+----------------------------------------+----------------------------------------+
                                         |
                                         v
+---------------------------------------------------------------------------------+
| 2. Perancangan Konseptual & Model Matematis                                     |
|    - Pemetaan skema Arteri-2 ke entitas RiC-CM v1.0                             |
|    - Formulasi Contextual Affinity Score (CAS) Multi-Komponen                   |
+----------------------------------------+----------------------------------------+
                                         |
                                         v
+---------------------------------------------------------------------------------+
| 3. Pengembangan Artefak Perangkat Lunak                                        |
|    - Implementasi RicContextualDiscoveryService (PHP 8.4, CodeIgniter 4)        |
|    - Modul serialisasi JSON-LD RiC-O RDF & Integrasi REST API / UI              |
+----------------------------------------+----------------------------------------+
                                         |
                                         v
+---------------------------------------------------------------------------------+
| 4. Demonstrasi & Evaluasi Empiris                                              |
|    - Pengujian Unit Test otomatis & Integrasi API                               |
|    - Benchmark komparatif Precision@K, Recall@10, dan MRR terhadap Baseline     |
+---------------------------------------------------------------------------------+
```

---

## Bagian 4. Perancangan dan Implementasi Artefak

### Bagian 4.1 Pemetaan Entitas dan Relasi Skema Basis Data
Skema basis data Arteri-2 dipetakan ke model konseptual RiC-CM sebagaimana ditunjukkan pada Tabel 1:

**Tabel 1. Pemetaan Skema Basis Data Arteri-2 ke Entitas RiC-CM**

| Entitas RiC-CM | Tabel & Kolom Basis Data Arteri-2 | Peran Semantik Kearsipan |
|---|---|---|
| `ric:Record` | `data_arsip` (`id`, `noarsip`, `uraian`, `tanggal`) | Entitas berkas/naskah arsip fisik maupun digital. |
| `ric:CorporateBody` | `master_pencipta` (`nama_pencipta`), `master_pengolah` | Lembaga pencipta dan unit pengolah arsip. |
| `ric:Activity` | `master_kode` (`kode`, `nama`) | Fungsi/urusan kerja yang tercermin dalam kode klasifikasi. |
| `ric:Place` | `master_lokasi` (`nama_lokasi`) | Tempat penyimpanan fisik/depo arsip. |

### Bagian 4.2 Formulasi Contextual Affinity Score ($CAS$)
Untuk menentukan kekuatan keterhubungan kontekstual antara arsip jangkar $R_{\text{seed}}$ dan arsip kandidat $R_{\text{cand}}$, dirumuskan nilai afinitas berbobot:

$$CAS(R_{\text{seed}}, R_{\text{cand}}) = w_{\text{agent}} \cdot \text{Aff}_{\text{agent}} + w_{\text{act}} \cdot \text{Aff}_{\text{act}} + w_{\text{temp}} \cdot \text{Aff}_{\text{temp}}$$

Dengan batasan bobot $\sum w = 1{,}0$ di mana ditetapkan $w_{\text{agent}} = 0{,}35$, $w_{\text{act}} = 0{,}45$, dan $w_{\text{temp}} = 0{,}20$.

1. **Afinitas Keagenan ($\text{Aff}_{\text{agent}}$):**
   $$\text{Aff}_{\text{agent}} = 0{,}6 \cdot \mathbb{I}(\text{pencipta}_{\text{seed}} = \text{pencipta}_{\text{cand}}) + 0{,}4 \cdot \mathbb{I}(\text{pengolah}_{\text{seed}} = \text{pengolah}_{\text{cand}})$$
   di mana $\mathbb{I}(\cdot)$ adalah fungsi indikator biner.

2. **Afinitas Fungsional ($\text{Aff}_{\text{act}}$):**
   $$\text{Aff}_{\text{act}} = \begin{cases} 1{,}0, & \text{jika } \text{kode}_{\text{seed}} = \text{kode}_{\text{cand}} \\ 0{,}5, & \text{jika } \text{rumpun}(\text{kode}_{\text{seed}}) = \text{rumpun}(\text{kode}_{\text{cand}}) \\ 0{,}0, & \text{lainnya} \end{cases}$$

3. **Afinitas Temporal ($\text{Aff}_{\text{temp}}$):**
   $$\text{Aff}_{\text{temp}} = \exp\left(-\frac{|\Delta \text{tanggal}|}{\tau}\right)$$
   di mana $\Delta \text{tanggal}$ adalah selisih hari penciptaan dan $\tau = 365\text{ hari}$ adalah konstanta peluruhan temporal.

### Bagian 4.3 Serialisasi Semantik JSON-LD RiC-O
Sistem menghasilkan representasi graf terstandar menggunakan format JSON-LD:

```json
{
  "@context": {
    "ric": "https://www.ica.org/standards/RiC/ontology#",
    "rdfs": "http://www.w3.org/2000/01/rdf-schema#"
  },
  "@graph": [
    {
      "@id": "http://localhost:8082/arsip/detail/1",
      "@type": "ric:Record",
      "ric:hasRecordIdentifier": "TI/2025/001",
      "ric:title": "Surat Keputusan Penetapan PPK Pengadaan Server TI 2025",
      "ric:hasCreator": {
        "@type": "ric:CorporateBody",
        "rdfs:label": "Pusat Data dan Informasi"
      },
      "ric:hasActivity": {
        "@type": "ric:Activity",
        "rdfs:label": "TI.01.01"
      },
      "ric:isContextuallyRelatedTo": [
        {
          "@id": "http://localhost:8082/arsip/detail/2",
          "@type": "ric:Record",
          "ric:hasRecordIdentifier": "TI/2025/002",
          "ric:contextualAffinityScore": 0.9984
        }
      ]
    }
  ]
}
```

---

## Bagian 5. Hasil dan Pembahasan

### Bagian 5.1 Hasil Evaluasi Benchmark
Pengujian benchmark dilakukan secara terkontrol menggunakan repositori simulasi proses bisnis kearsipan pemerintah (mencakup klaster pengadaan barang/jasa, rekrutmen pegawai, audit inspektorat, dan perencanaan anggaran) yang dilengkapi dengan data pengganggu (*noise records*). Metrik yang dievaluasi mencakup Precision@5, Precision@10, Cluster Recall@10, dan Mean Reciprocal Rank (MRR).

**Tabel 2. Hasil Perbandingan Metrik Evaluasi Empiris**

| Metode Penelusuran | Precision@5 ($\text{P@5}$) | Precision@10 ($\text{P@10}$) | Cluster Recall ($\text{Recall@10}$) | Mean Reciprocal Rank ($\text{MRR}$) |
|---|:---:|:---:|:---:|:---:|
| **Baseline (Kronologis Konvensional)** | $0{,}2522$ | $0{,}2609$ | $0{,}4720$ | $0{,}3446$ |
| **RiC Contextual Discovery** | **$0{,}8870$** | **$0{,}5130$** | **$1{,}0000$** | **$1{,}0000$** |
| **Peningkatan ($\Delta$)** | **$+0{,}6348$** | **$+0{,}2522$** | **$+0{,}5280$** | **$+0{,}6554$** |

### Bagian 5.2 Pembahasan Temuan
1. **Peningkatan Presisi pada Rekomendasi Teratas ($\text{P@5} = 0{,}8870$):**
   Pada pencarian konvensional, berkas yang disajikan hanya mengurutkan waktu transaksi terkini, sehingga dokumen penting yang dibuat beberapa bulan sebelumnya (misalnya Kerangka Acuan Kerja pada awal proyek pengadaan) terlempar ke halaman belakang. Sebaliknya, modul RiC berhasil mengelompokkan dokumen dalam satu rantai aktivitas dengan presisi $88{,}70\%$.
2. **Cakupan Klaster Penuh ($\text{Recall@10} = 1{,}0000$):**
   Seluruh berkas yang saling terikat dalam satu rangkaian transaksi kerja berhasil ditemukan dalam 10 rekomendasi pertama ($\text{Recall} = 100\%$). Hal ini sangat krusial bagi kepatuhan audit hukum kearsipan, di mana kelengkapan satu berkas (*completeness of records*) merupakan syarat keabsahan bukti.
3. **Ketepatan Peringkat Pertama ($\text{MRR} = 1{,}0000$):**
   Nilai MRR sempurna membuktikan bahwa sistem selalu berhasil menempatkan rekaman yang paling relevan secara kontekstual tepat pada posisi pertama daftar rekomendasi.

---

## Bagian 6. Kesimpulan dan Saran

Penelitian ini berhasil membuktikan bahwa pemodelan graf *Records in Contexts* (ICA RiC-CM v1.0) dapat dioperasionalkan secara efektif pada sistem informasi kearsipan berbasis basis data relasional seperti Arteri-2. Penerapan *Contextual Affinity Score* ($CAS$) terbukti meningkatkan efektivitas penemuan berkas terkait secara signifikan ($\Delta\text{P@5} = +0{,}6348$ dan $\text{Recall@10} = 1{,}0000$) dibandingkan metode konvensional. Selain itu, penyediaan antarmuka data terbuka berbasis JSON-LD RiC-O membuka peluang interoperabilitas data kearsipan antar-instansi pemerintah.

Saran untuk penelitian lanjutan mencakup: (i) penerapan evaluasi kualitatif berbasis uji keterbacaan kognitif pengguna (*user cognitive walkthrough*), dan (ii) integrasi pemrosesan graf skala besar (*graph database engine*) untuk repositori arsip berukuran jutaan rekaman.

---

## Daftar Pustaka

* Duranti, L. (1997). The archival bond. *Archives and Museum Informatics*, 11(3), 213–218. https://doi.org/10.1023/a:1009025127463
* ICA Expert Group on Archival Description. (2023). *Records in Contexts: Conceptual Model (RiC-CM v1.0)*. International Council on Archives. https://www.ica.org/standards/RiC/RiC-CM-1.0.pdf
* Llanes-Padrón, D., & Pastor-Sánchez, J. A. (2017). Records in contexts: the road of archives to semantic interoperability. *Program: electronic library and information systems*, 51(4), 387–405. https://doi.org/10.1108/prog-03-2017-0021
* MacNeil, H. (2000). *Trusting records: Legal, historical and diplomatic perspectives*. Springer Dordrecht. https://doi.org/10.1007/978-94-015-9375-5
* Peffers, K., Tuunanen, T., Rothenberger, M. A., & Chatterjee, S. (2007). A design science research methodology for information systems research. *Journal of Management Information Systems*, 24(3), 45–77. https://doi.org/10.2753/mis0742-1222240302
* Pitti, D., Stockting, B., & Clavaud, F. (2018). An introduction to “Records in Contexts”: an archival description draft standard. *Comma*, 2016(1-2), 173–188. https://doi.org/10.3828/comma.2016.18
