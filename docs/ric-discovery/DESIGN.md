# Rancangan Desain & Pemetaan Konseptual: Records in Contexts (RiC-CM) pada Arteri-2

## 1. Pendahuluan & Latar Belakang Teoretis

Dalam kearsipan modern, International Council on Archives (ICA) merilis **Records in Contexts (RiC)** sebagai standar deskripsi kearsipan generasi baru yang menggantikan ISAD(G) dan ISAAR(CPF). RiC mengadopsi pendekatan berbasis graf (*multidimensional context network*) yang memetakan entitas kearsipan beserta relasinya secara dinamis.

Pada sistem manajemen arsip konvensional seperti Arteri-2, data tersimpan dalam struktur tabel relasional datar (`data_arsip`). Akibatnya:
1. **Kehilangan Konteks Provenans Multidimensi:** Keterkaitan antara unit pencipta (*Agent*), urusan/fungsi (*Activity*), dan berkas arsip (*Record Resource*) hanya berupa *foreign-key/string matching* satu arah.
2. **Keterbatasan Temu Kembali Berkas Terkait (*Dossier Discovery*):** Pengguna tidak dapat menelusuri berkas yang saling berhubungan secara proses bisnis jika tidak memiliki kata kunci atau nomor berkas yang sama.

Artefak ini merancang modul **Contextual Discovery Engine** berbasis entitas dan relasi RiC-CM v1.0 pada Arteri-2.

---

## 2. Pemetaan Entitas RiC-CM v1.0 ke Skema Arteri-2

| Entitas RiC-CM (v1.0) | Atribut / Entitas Arteri-2 | Deskripsi Kearsipan |
|---|---|---|
| **`ric:RecordResource`** (sub-tipe: `RecordSet` / `Record`) | `data_arsip` (`id`, `noarsip`, `uraian`, `tanggal`) | Entitas fisik/logis dari berkas atau naskah dinamis arsip. |
| **`ric:Agent`** (sub-tipe: `CorporateBody`) | `master_pencipta`, `master_pengolah` | Lembaga/unit kerja yang menciptakan, mengelola, atau memiliki tanggung jawab atas arsip. |
| **`ric:Activity` / `ric:Function`** | `master_kode` (`kode`, `nama`) | Fungsi atau kegiatan kerja yang menghasilkan arsip (direpresentasikan melalui kode klasifikasi). |
| **`ric:Date`** | `tanggal`, `tgl_input`, retensi JRA | Dimensi temporal yang menandai waktu penciptaan dan siklus hidup arsip. |
| **`ric:Place`** | `master_lokasi` | Lokasi fisik/depo penyimpanan arsip. |

---

## 3. Pemetaan Relasi RiC (RiC Relations)

Hubungan antar-entitas dalam Arteri-2 dipetakan ke relasi standar RiC:

```
+-----------------------------------------------------------------------------------+
|                                  [ric:Activity]                                   |
|                          (master_kode: Kode Klasifikasi)                          |
+-----------------------------------------+-----------------------------------------+
                                          ^
                                          | ric:isActivityAssociatedWithRecordResource
                                          | / ric:hasActivity
                                          v
+-----------------------+     ric:isCreatedBy     +---------------------------------+
|      [ric:Agent]      |<------------------------|       [ric:RecordResource]      |
|  (master_pencipta /   |------------------------>|           (data_arsip)          |
|    master_pengolah)   |     ric:createdRecord   +---------------------------------+
+-----------------------+                                 |          |
                                                          |          | ric:hasLocation
            ric:isRelatedTo (Contextual Network)          |          v
          <-----------------------------------------------+   +---------------------+
                                                              |     [ric:Place]     |
                                                              |   (master_lokasi)   |
                                                              +---------------------+
```

Relasi eksplisit:
1. `ric:hasCreator` / `ric:isCreatedBy`: Relasi antara `data_arsip` dengan `master_pencipta`.
2. `ric:hasManagingAgent`: Relasi antara `data_arsip` dengan `master_pengolah`.
3. `ric:hasActivity`: Relasi antara `data_arsip` dengan `master_kode` (Klasifikasi Urusan).
4. `ric:isContextuallyRelatedTo`: Relasi turunan antar-arsip (`RecordResource` ↔ `RecordResource`) yang berbagi klaster fungsional (*Shared Activity + Shared Agent* atau ko-okurensi proses bisnis).

---

## 4. Arsitektur Contextual Discovery Engine pada Arteri-2

### 4.1 Mekanisme Traversal Kontekstual
Ketika sebuah arsip $R_A$ dibuka atau dijadikan jangkar kueri (*seed record*), mesin penelusur RiC menghitung skor keterkaitan kontekstual (*Contextual Affinity Score*, $CAS$) terhadap arsip kandidat $R_B$:

$$CAS(R_A, R_B) = \alpha \cdot \text{AgentAffinity}(R_A, R_B) + \beta \cdot \text{ActivityAffinity}(R_A, R_B) + \gamma \cdot \text{TemporalAffinity}(R_A, R_B)$$

Di mana:
* $\text{AgentAffinity}(R_A, R_B)$: Kesamaan/kedekatan hierarki unit pencipta dan unit pengolah ($1$ jika identik, $0{,}5$ jika satu divisi/induk).
* $\text{ActivityAffinity}(R_A, R_B)$: Kesamaan kode klasifikasi fungsi/urusan ($1$ jika kode identik, $0{,}6$ jika berada dalam rumpun induk kode yang sama misal `KU.01` dan `KU.02`).
* $\text{TemporalAffinity}(R_A, R_B) = \exp\left(-\frac{|\Delta \text{tanggal}|}{\tau}\right)$: Kedekatan waktu eksekusi kegiatan.

### 4.2 Komponen Teknis yang Dibangun
1. **`App\Services\RicContextualDiscoveryService`**:
   * Layanan penelusuran relasi RiC (menghasilkan *Context Graph* dan daftar rekaman terkait berbasis CAS).
   * Generator ekspor semantik JSON-LD (`application/ld+json`) sesuai standar RiC-O.
2. **Endpoint & UI View (`/arsip/context/{id}` & Modal Graph View)**:
   * Menampilkan visualisasi jaringan berkas terkait (*provenance network / cluster*).
   * Menyajikan *Related Dossiers* pada halaman detail arsip.
3. **Benchmark Test & Evaluation Suite**:
   * Menilai efektivitas penemuan berkas terkait (*Contextual Cluster Precision/Recall*).
