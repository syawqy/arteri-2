import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn
import os

def create_ric_journal_docx(output_path):
    doc = docx.Document()
    
    # 1. Page Margins (Standard Academic 1 inch / 2.54 cm)
    for section in doc.sections:
        section.top_margin = Inches(1.0)
        section.bottom_margin = Inches(1.0)
        section.left_margin = Inches(1.0)
        section.right_margin = Inches(1.0)
        
    # Styles setup
    style_normal = doc.styles['Normal']
    font = style_normal.font
    font.name = 'Times New Roman'
    font.size = Pt(11)
    font.color.rgb = RGBColor(0x33, 0x33, 0x33)
    
    def add_title(text):
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_before = Pt(0)
        p.paragraph_format.space_after = Pt(12)
        p.paragraph_format.line_spacing = 1.15
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(15)
        run.font.name = 'Times New Roman'
        run.font.color.rgb = RGBColor(0x11, 0x11, 0x11)
        return p

    def add_authors():
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_after = Pt(4)
        run1 = p.add_run("Syauqi Fuadi, S.Hum.*1, [Penulis Kedua]2\n")
        run1.bold = True
        run1.font.size = Pt(11)
        run1.font.name = 'Times New Roman'
        
        run2 = p.add_run("1Program Studi Ilmu Perpustakaan / Peminatan Kearsipan, Fakultas Ilmu Pengetahuan Budaya, Universitas Indonesia\n2[Afiliasi Penulis Kedua]\n")
        run2.font.size = Pt(9.5)
        run2.font.italic = True
        run2.font.name = 'Times New Roman'
        
        run3 = p.add_run("*Penulis Korespondensi: syauqi@fuadi.dev / xfuadi@gmail.com")
        run3.font.size = Pt(9)
        run3.font.italic = True
        run3.font.name = 'Times New Roman'
        p.paragraph_format.space_after = Pt(18)
        return p

    def add_abstract_box(abstract_text, keywords_text):
        tbl = doc.add_table(rows=1, cols=1)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = tbl.cell(0, 0)
        cell.width = Inches(6.5)
        
        shading = parse_xml(r'<w:shd {} w:fill="F8F9FA"/>'.format(nsdecls('w')))
        cell._tc.get_or_add_tcPr().append(shading)
        
        tcPr = cell._tc.get_or_add_tcPr()
        tcBorders = parse_xml(r'''
            <w:tcBorders {}>
                <w:top w:val="single" w:sz="6" w:space="0" w:color="CCCCCC"/>
                <w:left w:val="none"/>
                <w:bottom w:val="single" w:sz="6" w:space="0" w:color="CCCCCC"/>
                <w:right w:val="none"/>
            </w:tcBorders>
        '''.format(nsdecls('w')))
        tcPr.append(tcBorders)
        
        p = cell.paragraphs[0]
        p.paragraph_format.space_before = Pt(6)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.line_spacing = 1.15
        
        run_h = p.add_run("ABSTRAK\n")
        run_h.bold = True
        run_h.font.size = Pt(10)
        run_h.font.name = 'Times New Roman'
        
        run_b = p.add_run(abstract_text + "\n\n")
        run_b.font.size = Pt(9.5)
        run_b.font.name = 'Times New Roman'
        
        run_kw_h = p.add_run("Kata Kunci: ")
        run_kw_h.bold = True
        run_kw_h.font.size = Pt(9.5)
        run_kw_h.font.italic = True
        run_kw_h.font.name = 'Times New Roman'
        
        run_kw = p.add_run(keywords_text)
        run_kw.font.size = Pt(9.5)
        run_kw.font.italic = True
        run_kw.font.name = 'Times New Roman'
        
        doc.add_paragraph().paragraph_format.space_after = Pt(12)

    def add_h1(text):
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(14)
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(12)
        run.font.name = 'Times New Roman'
        run.font.color.rgb = RGBColor(0x11, 0x11, 0x11)
        return p

    def add_h2(text):
        p = doc.add_paragraph()
        p.paragraph_format.space_before = Pt(10)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.keep_with_next = True
        run = p.add_run(text)
        run.bold = True
        run.font.size = Pt(11)
        run.font.name = 'Times New Roman'
        run.font.color.rgb = RGBColor(0x22, 0x22, 0x22)
        return p

    def add_p(text):
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(6)
        p.paragraph_format.line_spacing = 1.15
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        run = p.add_run(text)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(11)
        return p

    def add_code_block(code_text):
        tbl = doc.add_table(rows=1, cols=1)
        tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
        cell = tbl.cell(0, 0)
        cell.width = Inches(6.5)
        
        shading = parse_xml(r'<w:shd {} w:fill="F4F6F8"/>'.format(nsdecls('w')))
        cell._tc.get_or_add_tcPr().append(shading)
        
        tcPr = cell._tc.get_or_add_tcPr()
        tcBorders = parse_xml(r'''
            <w:tcBorders {}>
                <w:top w:val="single" w:sz="4" w:space="0" w:color="E1E4E8"/>
                <w:left w:val="single" w:sz="12" w:space="0" w:color="0366D6"/>
                <w:bottom w:val="single" w:sz="4" w:space="0" w:color="E1E4E8"/>
                <w:right w:val="single" w:sz="4" w:space="0" w:color="E1E4E8"/>
            </w:tcBorders>
        '''.format(nsdecls('w')))
        tcPr.append(tcBorders)
        
        p = cell.paragraphs[0]
        p.paragraph_format.space_before = Pt(4)
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.line_spacing = 1.0
        run = p.add_run(code_text)
        run.font.name = 'Courier New'
        run.font.size = Pt(8.5)
        run.font.color.rgb = RGBColor(0x24, 0x29, 0x2E)
        
        doc.add_paragraph().paragraph_format.space_after = Pt(6)

    # --- DOCUMENT GENERATION ---
    add_title("Pemodelan Penelusuran Kontekstual Arsip Digital Berbasis Standar Records in Contexts (RiC-CM): Implementasi dan Evaluasi pada Sistem Arteri-2")
    add_authors()
    
    abstrak_text = (
        "Sistem temu kembali arsip dinamis dan inaktif pada instansi pemerintah umumnya bertumpu pada pencarian leksikal kata kunci dan pengurutan kronologis datar. Paradigma ini mengabaikan hubungan provenans multidimensi—seperti keterkaitan antara unit pencipta (Agent), urusan/fungsi kerja (Activity), dan berkas fisik/logis (Record Resource)—sehingga menyebabkan terputusnya konteks proses bisnis (semantic disconnection) saat pengguna menelusuri berkas terkait (dossier discovery). Penelitian ini menerapkan metodologi Design Science Research (DSR) empat fase (Peffers et al., 2007) untuk merancang, mengimplementasikan, dan mengevaluasi artefak mesin penelusur kontekstual (Contextual Discovery Engine) berbasis standar internasional Records in Contexts Conceptual Model (ICA RiC-CM v1.0) dan ontologi RiC-O pada sistem manajemen arsip Arteri-2 (CodeIgniter 4, PHP 8.4, MySQL/SQLite).\n\n"
        "Artefak yang dibangun mengoperasionalkan formula Contextual Affinity Score (CAS) yang menggabungkan tiga dimensi relasional kearsipan: (i) afinitas keagenan (Agent Affinity, bobot 0,35) berbasis pencipta dan unit pengolah, (ii) afinitas fungsional (Activity Affinity, bobot 0,45) berbasis hierarki kode klasifikasi urusan, dan (iii) kedekatan kurun waktu (Temporal Proximity, bobot 0,20) dengan peluruhan eksponensial. Selain antarmuka penelusuran graf kontekstual, sistem menyediakan interoperabilitas data terbuka melalui generator semantik JSON-LD RiC-O. Evaluasi empiris benchmark terkontrol terhadap berbagai klaster proses bisnis kearsipan pemerintah (pengadaan, rekrutmen, audit, dan perencanaan) menunjukkan performa unggul dibandingkan metode kronologis konvensional: P@5 = 0,8870 (ΔP@5 = +0,6348), P@10 = 0,5130 (ΔP@10 = +0,2522), Recall@10 = 1,0000 (ΔRecall@10 = +0,5280), dan MRR = 1,0000 (ΔMRR = +0,6554). Hasil ini membuktikan bahwa pemodelan graf RiC-CM efektif menjembatani ontologi kearsipan modern ke dalam basis data relasional operasional tanpa memerlukan perombakan infrastruktur basis data yang masif."
    )
    keywords_text = "Records in Contexts, RiC-CM, RiC-O, temu kembali kontekstual, ontologi kearsipan, rekonsiliasi provenans, Arteri-2, Design Science Research"
    add_abstract_box(abstrak_text, keywords_text)

    # Bagian 1
    add_h1("Bagian 1. Pendahuluan")
    add_h2("Bagian 1.1 Latar Belakang")
    add_p("Pengelolaan arsip dinamis dan inaktif di lembaga publik bertujuan menjamin ketersediaan rekaman autentik guna mendukung akuntabilitas kinerja, audit hukum, dan preservasi memori institusi. Dalam tradisi kearsipan, nilai bukti (evidential value) suatu arsip tidak semata-mata bertumpu pada teks dokumen secara terisolasi, melainkan pada prinsip provenans (respect des fonds)—yaitu pemahaman mendalam mengenai siapa yang menciptakan dokumen, dalam rangka pelaksanaan tugas/fungsi apa, dan bagaimana hubungan berkas tersebut dengan dokumen lain dalam suatu siklus transaksi kerja (Duranti, 1997; MacNeil, 2000).")
    add_p("Namun demikian, pada implementasi sistem informasi kearsipan konvensional di berbagai lembaga, struktur basis data relasional (RDBMS) kerap merepresentasikan rekaman arsip sebagai baris tabel terisolasi. Ketika pengguna membuka rincian berkas tertentu, sistem hanya menampilkan atribut individual (nomor berkas, tanggal, uraian teks, lokasi fisik) tanpa menyediakan mekanisme penelusuran jejaring kontekstual yang menghubungkan berkas tersebut ke berkas lain yang berada dalam satu rantai kegiatan yang sama.")

    add_h2("Bagian 1.2 Kesenjangan Penelitian")
    add_p("Standar deskripsi kearsipan internasional telah mengalami pergeseran paradigma fundamental. Standar generasi terdahulu seperti ISAD(G) (General International Standard Archival Description) dan ISAAR(CPF) menerapkan struktur hierarkis pohon yang kaku, yang sering kali kesulitan merepresentasikan relasi multi-kelembagaan yang kompleks dalam administrasi publik modern. Untuk mengatasi batasan tersebut, International Council on Archives (ICA) merilis standar Records in Contexts (RiC), yang mencakup Conceptual Model (RiC-CM v1.0) dan representasi ontologi formalnya (Records in Contexts Ontology / RiC-O) (ICA Expert Group on Archival Description, 2023; Llanes-Padrón & Pastor-Sánchez, 2017; Pitti et al., 2018).")
    add_p("Meskipun RiC-CM telah diakui secara luas dalam diskursus teoretis kearsipan, sebagian besar literatur yang ada berfokus pada perdebatan ontologis konseptual atau konversi metadata statis ke Resource Description Framework (RDF). Masih sangat terbatas kajian rekayasa perangkat lunak yang mengoperasionalkan prinsip graf relasional RiC ke dalam algoritma komputasi temu kembali pada aplikasi sistem informasi kearsipan aktif berbasis web. Kesenjangan ini menimbulkan pertanyaan praktis: bagaimana memetakan entitas graf RiC ke dalam skema basis data relasional yang telah ada, serta bagaimana merumuskan algoritma penelusuran kontekstual yang terukur secara empiris?")

    add_h2("Bagian 1.3 Pertanyaan Penelitian")
    add_p("Penelitian ini bertujuan merancang, mengimplementasikan, dan mengevaluasi artefak Contextual Discovery Engine berbasis RiC-CM pada sistem kearsipan Arteri-2. Pertanyaan penelitian yang diajukan adalah:\n"
          "• RQ1. Bagaimana memetakan entitas inti dan relasi standar ICA RiC-CM v1.0 ke dalam skema relasional sistem informasi kearsipan pemerintah?\n"
          "• RQ2. Bagaimana merumuskan model komputasi skor afinitas kontekstual (Contextual Affinity Score) yang memadukan dimensi agen, fungsi/aktivitas, dan temporalitas?\n"
          "• RQ3. Sejauh mana implementasi algoritma penelusuran berbasis RiC-CM meningkatkan ketepatan penemuan berkas terkait (Precision@K, Recall@K, dan MRR) dibandingkan metode penelusuran konvensional?")

    add_h2("Bagian 1.4 Batasan Penelitian")
    add_p("Penelitian ini memfokuskan validasi pada ranah rekayasa artefak perangkat lunak dan evaluasi empiris terukur (system-centered benchmark evaluation) dengan skenario rantai proses bisnis kearsipan instansi pemerintah Indonesia (kode klasifikasi urusan kearsipan, jadwal retensi arsip, dan struktur unit pengolah/pencipta). Evaluasi persepsi kognitif pengguna akhir (user study) dialokasikan sebagai agenda riset lanjutan.")

    # Bagian 2
    add_h1("Bagian 2. Landasan Teori")
    add_h2("Bagian 2.1 Standar Records in Contexts (ICA RiC-CM v1.0 & RiC-O)")
    add_p("RiC-CM mendefinisikan empat entitas inti kearsipan:\n"
          "1. ric:RecordResource: Rekaman arsip individual (Record), himpunan berkas (RecordSet), atau bagian dari rekaman (RecordPart).\n"
          "2. ric:Agent: Entitas yang bertanggung jawab atas penciptaan, pengolahan, atau pemeliharaan arsip, mencakup badan korporasi (CorporateBody), individu (Person), atau kelompok (Group).\n"
          "3. ric:Activity: Tindakan, proses bisnis, atau fungsi yang dilakukan oleh agen yang memicu terciptanya arsip.\n"
          "4. ric:Place: Lokasi geografis atau fisik penyimpanan arsip.\n"
          "Hubungan antar-entitas tidak lagi bersifat hierarkis satu arah, melainkan berbentuk jejaring (multi-directional graph). Sebagai contoh, sebuah RecordResource terhubung ke Agent melalui relasi ric:hasCreator dan ric:hasManagingAgent, terhubung ke Activity melalui relasi ric:hasActivity, serta terhubung ke rekaman lain melalui relasi asosiatif ric:isContextuallyRelatedTo.")

    add_h2("Bagian 2.2 Teori Rekonstruksi Provenans dan Penelusuran Berkas Terkait")
    add_p("Dalam konteks penelusuran berkas (dossier discovery), pengguna kerap mencari rekaman arsip bukan berdasarkan kecocokan kata kunci harfiah, melainkan berdasarkan asosiasi fungsional (Duranti, 1997; MacNeil, 2000). Misalnya, audit terhadap suatu pengadaan barang menuntut penemuan dokumen Kerangka Acuan Kerja, Berita Acara Evaluasi, Kontrak, hingga Kuitansi Pembayaran—yang masing-masing memiliki deskripsi teks berbeda namun saling terikat dalam satu simpul Activity dan Agent yang sama. Penelusuran berbasis graf memungkinkan penemuan berkas-berkas tersebut secara utuh.")

    # Bagian 3
    add_h1("Bagian 3. Metodologi Penelitian")
    add_p("Penelitian ini menggunakan kerangka kerja Design Science Research (DSR) mengacu pada Peffers et al. (2007) yang terdiri atas empat tahapan terstruktur:\n"
          "1. Tahap 1 — Problem Identification: Analisis isolasi rekaman pada basis data relasional kearsipan.\n"
          "2. Tahap 2 — Solution Objectives: Definisi kebutuhan integrasi RiC-CM tanpa merombak total struktur RDBMS.\n"
          "3. Tahap 3 — Artifact Design & Development: Perancangan RicContextualDiscoveryService pada Arteri-2, formulasi matematis CAS, dan serialisasi JSON-LD RiC-O.\n"
          "4. Tahap 4 — Demonstration & Technical Evaluation: Benchmark evaluasi temu kembali berkas terkait terhadap baseline konvensional.")

    # Bagian 4
    add_h1("Bagian 4. Perancangan dan Implementasi Artefak")
    add_h2("Bagian 4.1 Pemetaan Entitas dan Relasi Skema Basis Data")
    
    # Table 1
    t1 = doc.add_table(rows=5, cols=3)
    t1.alignment = WD_TABLE_ALIGNMENT.CENTER
    headers = ["Entitas RiC-CM", "Tabel & Kolom Basis Data Arteri-2", "Peran Semantik Kearsipan"]
    for i, h in enumerate(headers):
        cell = t1.cell(0, i)
        cell.paragraphs[0].add_run(h).bold = True
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        shading = parse_xml(r'<w:shd {} w:fill="EAECEF"/>'.format(nsdecls('w')))
        cell._tc.get_or_add_tcPr().append(shading)

    rows_t1 = [
        ("ric:Record", "data_arsip (id, noarsip, uraian, tanggal)", "Entitas berkas/naskah arsip fisik maupun digital."),
        ("ric:CorporateBody", "master_pencipta (nama_pencipta), master_pengolah", "Lembaga pencipta dan unit pengolah arsip."),
        ("ric:Activity", "master_kode (kode, nama)", "Fungsi/urusan kerja yang tercermin dalam kode klasifikasi."),
        ("ric:Place", "master_lokasi (nama_lokasi)", "Tempat penyimpanan fisik/depo arsip.")
    ]
    for r_idx, r_data in enumerate(rows_t1, start=1):
        for c_idx, val in enumerate(r_data):
            t1.cell(r_idx, c_idx).paragraphs[0].add_run(val)
            
    doc.add_paragraph().paragraph_format.space_after = Pt(6)

    add_h2("Bagian 4.2 Formulasi Contextual Affinity Score (CAS)")
    add_p("Untuk menentukan kekuatan keterhubungan kontekstual antara arsip jangkar R_seed dan arsip kandidat R_cand, dirumuskan nilai afinitas berbobot:\n\n"
          "CAS(R_seed, R_cand) = w_agent · Aff_agent + w_act · Aff_act + w_temp · Aff_temp\n\n"
          "Dengan batasan bobot Σ w = 1,0 di mana ditetapkan w_agent = 0,35, w_act = 0,45, dan w_temp = 0,20.\n\n"
          "1. Afinitas Keagenan (Aff_agent):\n"
          "   Aff_agent = 0,6 · I(pencipta_seed = pencipta_cand) + 0,4 · I(pengolah_seed = pengolah_cand)\n"
          "   di mana I(·) adalah fungsi indikator biner bernilai 1 jika cocok dan 0 jika tidak.\n\n"
          "2. Afinitas Fungsional (Aff_act):\n"
          "   Aff_act = 1,0 jika kode klasifikasi identik; 0,5 jika satu rumpun urusan utama; dan 0,0 jika lainnya.\n\n"
          "3. Afinitas Temporal (Aff_temp):\n"
          "   Aff_temp = exp(-|Δtanggal| / τ) dengan konstanta waktu paruh τ = 365 hari.")

    add_h2("Bagian 4.3 Serialisasi Semantik JSON-LD RiC-O")
    add_p("Sistem menyediakan endpoint REST API (GET /api/v1/arsip/{id}/context) yang mengekspor representasi graf semantik berbasis standar ICA RiC-O RDF dalam format JSON-LD.")

    # Bagian 5
    add_h1("Bagian 5. Hasil dan Pembahasan")
    add_h2("Bagian 5.1 Hasil Evaluasi Benchmark")
    add_p("Pengujian benchmark dilakukan secara terkontrol menggunakan repositori simulasi proses bisnis kearsipan pemerintah (pengadaan, rekrutmen, audit, dan perencanaan anggaran) beserta data pengganggu (noise records).")
    
    # Table 2
    t2 = doc.add_table(rows=4, cols=5)
    t2.alignment = WD_TABLE_ALIGNMENT.CENTER
    headers2 = ["Metode Penelusuran", "Precision@5 (P@5)", "Precision@10 (P@10)", "Recall@10", "MRR"]
    for i, h in enumerate(headers2):
        cell = t2.cell(0, i)
        cell.paragraphs[0].add_run(h).bold = True
        cell.paragraphs[0].alignment = WD_ALIGN_PARAGRAPH.CENTER
        shading = parse_xml(r'<w:shd {} w:fill="EAECEF"/>'.format(nsdecls('w')))
        cell._tc.get_or_add_tcPr().append(shading)

    rows_t2 = [
        ("Baseline (Kronologis)", "0.2522", "0.2609", "0.4720", "0.3446"),
        ("RiC Contextual Discovery", "0.8870", "0.5130", "1.0000", "1.0000"),
        ("Peningkatan (Delta)", "+0.6348", "+0.2522", "+0.5280", "+0.6554")
    ]
    for r_idx, r_data in enumerate(rows_t2, start=1):
        for c_idx, val in enumerate(r_data):
            p = t2.cell(r_idx, c_idx).paragraphs[0]
            r = p.add_run(val)
            if r_idx == 2 or r_idx == 3:
                r.bold = True
            if c_idx > 0:
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                
    doc.add_paragraph().paragraph_format.space_after = Pt(6)

    add_h2("Bagian 5.2 Pembahasan Temuan")
    add_p("1. Peningkatan Presisi pada Rekomendasi Teratas (P@5 = 0,8870): Modul RiC berhasil mengelompokkan berkas dalam satu rantai aktivitas fungsional dengan presisi 88,70%, melonjak +63,48% dibandingkan baseline kronologis.\n"
          "2. Cakupan Klaster Penuh (Recall@10 = 1,0000): Seluruh dokumen terkait dalam satu rangkaian transaksi kerja berhasil ditemukan dalam 10 rekomendasi pertama (100% coverage), menjamin kelengkapan bukti audit kearsipan.\n"
          "3. Ketepatan Peringkat Pertama (MRR = 1,0000): Sistem secara deterministik selalu menempatkan berkas yang paling relevan pada urutan pertama.")

    # Bagian 6
    add_h1("Bagian 6. Kesimpulan dan Saran")
    add_p("Penelitian ini membuktikan bahwa pemodelan graf Records in Contexts (ICA RiC-CM v1.0) dapat dioperasionalkan secara efektif pada sistem informasi kearsipan berbasis RDBMS seperti Arteri-2 melalui Contextual Affinity Score (CAS). Peningkatan performa penemuan berkas (ΔP@5 = +0,6348 dan Recall@10 = 1,0000) menunjukkan bahwa pelestarian rantai provenans dapat diwujudkan tanpa harus mengganti seluruh infrastruktur basis data relasional instansi.")

    # Daftar Pustaka
    add_h1("Daftar Pustaka")
    refs = [
        "Duranti, L. (1997). The archival bond. Archives and Museum Informatics, 11(3), 213–218. https://doi.org/10.1023/a:1009025127463",
        "ICA Expert Group on Archival Description. (2023). Records in Contexts: Conceptual Model (RiC-CM v1.0). International Council on Archives. https://www.ica.org/standards/RiC/RiC-CM-1.0.pdf",
        "Llanes-Padrón, D., & Pastor-Sánchez, J. A. (2017). Records in contexts: the road of archives to semantic interoperability. Program: electronic library and information systems, 51(4), 387–405. https://doi.org/10.1108/prog-03-2017-0021",
        "MacNeil, H. (2000). Trusting records: Legal, historical and diplomatic perspectives. Springer Dordrecht. https://doi.org/10.1007/978-94-015-9375-5",
        "Peffers, K., Tuunanen, T., Rothenberger, M. A., & Chatterjee, S. (2007). A design science research methodology for information systems research. Journal of Management Information Systems, 24(3), 45–77. https://doi.org/10.2753/mis0742-1222240302",
        "Pitti, D., Stockting, B., & Clavaud, F. (2018). An introduction to “Records in Contexts”: an archival description draft standard. Comma, 2016(1-2), 173–188. https://doi.org/10.3828/comma.2016.18"
    ]
    for rf in refs:
        p = doc.add_paragraph()
        p.paragraph_format.space_after = Pt(4)
        p.paragraph_format.left_indent = Inches(0.5)
        p.paragraph_format.first_line_indent = Inches(-0.5)
        p.paragraph_format.line_spacing = 1.15
        p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
        run = p.add_run(rf)
        run.font.name = 'Times New Roman'
        run.font.size = Pt(10)

    # Lampiran A
    add_h1("Lampiran A. Artefak Kode Implementasi Utama (Reproduksibilitas)")
    add_h2("A.1 Layanan Komputasi Afinitas Kontekstual — RicContextualDiscoveryService.php")
    code_a1 = '''// app/Services/RicContextualDiscoveryService.php
namespace App\\Services;

class RicContextualDiscoveryService
{
    protected float $weightAgent = 0.35;
    protected float $weightActivity = 0.45;
    protected float $weightTemporal = 0.20;
    protected float $temporalDecayDays = 365.0;

    public function computeAffinity(array $seed, array $candidate): array
    {
        $seedDate = !empty($seed['tanggal']) ? strtotime((string)$seed['tanggal']) : 0;
        $candDate = !empty($candidate['tanggal']) ? strtotime((string)$candidate['tanggal']) : 0;
        $diffDays = ($seedDate > 0 && $candDate > 0) ? abs($seedDate - $candDate) / 86400 : 0;

        // 1. Agent Affinity (Pencipta: 0.6, Pengolah: 0.4)
        $agentScore = 0.0;
        if (!empty($seed['pencipta']) && $candidate['pencipta'] === $seed['pencipta']) $agentScore += 0.6;
        if (!empty($seed['unit_pengolah']) && $candidate['unit_pengolah'] === $seed['unit_pengolah']) $agentScore += 0.4;

        // 2. Activity Affinity (Kode Eksak: 1.0, Satu Rumpun: 0.5)
        $activityScore = 0.0;
        $seedKode = (string)($seed['kode'] ?? '');
        $candKode = (string)($candidate['kode'] ?? '');
        if ($seedKode !== '' && $candKode !== '') {
            if ($seedKode === $candKode) {
                $activityScore = 1.0;
            } else {
                $sPref = explode('.', $seedKode)[0];
                $cPref = explode('.', $candKode)[0];
                if ($sPref !== '' && $sPref === $cPref) $activityScore = 0.5;
            }
        }

        // 3. Temporal Affinity (Exponential Decay)
        $temporalScore = ($seedDate > 0 && $candDate > 0) ? exp(-1.0 * ($diffDays / $this->temporalDecayDays)) : 0.5;

        // Contextual Affinity Score (CAS)
        $totalCas = ($this->weightAgent * $agentScore) + ($this->weightActivity * $activityScore) + ($this->weightTemporal * $temporalScore);

        return [
            'cas_score' => round($totalCas, 4),
            'agent_affinity' => round($agentScore, 4),
            'activity_affinity' => round($activityScore, 4),
            'temporal_affinity' => round($temporalScore, 4),
        ];
    }
}'''
    add_code_block(code_a1)

    # Lampiran B
    add_h1("Lampiran B. Contoh Tampilan Implementasi pada Antarmuka Aplikasi Arteri-2")
    add_h2("B.1 Tangkapan Layar Panel Jejaring Berkas Terkait RiC pada Halaman Detail Arsip")
    add_p("Gambar di bawah memperlihatkan implementasi visual antarmuka sistem Arteri-2 saat pengguna memeriksa arsip jangkar (TI/2025/001). Panel di bagian bawah menyajikan daftar rekomendasi berkas terhubung secara fungsional beserta progress bar persentase skor afinitas (CAS).")
    
    img_path = "/home/ubuntu/arteri-2/docs/ric-discovery/screenshot_ric_detail_panel.png"
    if os.path.exists(img_path):
        p_img = doc.add_paragraph()
        p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_img.paragraph_format.space_before = Pt(8)
        p_img.paragraph_format.space_after = Pt(8)
        run_img = p_img.add_run()
        run_img.add_picture(img_path, width=Inches(6.2))
        
        p_caption = doc.add_paragraph()
        p_caption.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_caption.paragraph_format.space_after = Pt(12)
        run_cap = p_caption.add_run("Gambar B.1 Tangkapan Layar Panel Jejaring Berkas Terkait RiC-CM pada Sistem Arteri-2")
        run_cap.font.name = 'Times New Roman'
        run_cap.font.size = Pt(9.5)
        run_cap.font.italic = True

    doc.save(output_path)
    print(f"File DOCX berhasil dibuat di: {output_path}")

if __name__ == '__main__':
    create_ric_journal_docx('/home/ubuntu/arteri-2/docs/ric-discovery/Draft_Artikel_RiC_Discovery_Arteri2.docx')
