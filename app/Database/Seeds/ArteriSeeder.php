<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ArteriSeeder extends Seeder
{
    public function run(): void
    {
        // master_kode
        $this->db->table('master_kode')->insertBatch([
            ['kode' => 'SDM.01',   'nama' => 'Rekrutmen Pegawai',       'retensi' => 1],
            ['kode' => 'SDM.02',   'nama' => 'Mutasi Pegawai',          'retensi' => 1],
            ['kode' => 'SDM.03',   'nama' => 'Pengembangan Pegawai',    'retensi' => 1],
            ['kode' => 'SDM.04',   'nama' => 'Cuti Pegawai',            'retensi' => 3],
            ['kode' => 'SDM.03.01','nama' => 'Pelatihan Pegawai',       'retensi' => 1],
            ['kode' => 'SDM.03.02','nama' => 'Beasiswa Pegawai',        'retensi' => 1],
            ['kode' => 'SDM.01.01','nama' => 'Pengangakatan Pegawai',   'retensi' => 1],
            ['kode' => 'SDM.05',   'nama' => 'Pemberhentian Pegawai',   'retensi' => 5],
            ['kode' => 'KEU.01',   'nama' => 'Rencana Anggaran',        'retensi' => 10],
            ['kode' => 'KEU.02',   'nama' => 'Realisasi Anggaran Pegawai', 'retensi' => 10],
            ['kode' => 'KEU.03',   'nama' => 'Realisasi Anggaran Umum dan Rumah Tangga', 'retensi' => 10],
            ['kode' => 'HKP.01',   'nama' => 'Peraturan Perusahaan',    'retensi' => 3],
            ['kode' => 'HKP.01.01','nama' => 'Peraturan Direksi Perusahaan', 'retensi' => 5],
            ['kode' => 'HKP.01.02','nama' => 'Keputusan Direksi Perusahaan', 'retensi' => 5],
            ['kode' => 'HKP.02',   'nama' => 'Pengawasan Internal',     'retensi' => 10],
            ['kode' => 'RND.01',   'nama' => 'Penelitian dan Pengembangan', 'retensi' => 3],
            ['kode' => 'UMUM.01',  'nama' => 'Inventarisasi Barang Bergerak', 'retensi' => 5],
            ['kode' => 'UMUM.02',  'nama' => 'Inventarisasi Barang Tidak Bergerak', 'retensi' => 5],
        ]);

        // master_lokasi
        $this->db->table('master_lokasi')->insertBatch([
            ['nama_lokasi' => 'Gedung A, Unit II'],
            ['nama_lokasi' => 'Gedung B, Unit III'],
            ['nama_lokasi' => 'Gedung C, Unit IV'],
            ['nama_lokasi' => 'Lokasi'],
            ['nama_lokasi' => 'Gedung C lt 2'],
            ['nama_lokasi' => 'Gedung B lt4'],
        ]);

        // master_media
        $this->db->table('master_media')->insertBatch([
            ['nama_media' => 'Tekstual'],
            ['nama_media' => 'Kartografi'],
            ['nama_media' => 'Blueprint'],
            ['nama_media' => 'Audio Cassette'],
            ['nama_media' => 'Audio Disc'],
            ['nama_media' => 'Video Cartridge'],
            ['nama_media' => 'Digital'],
            ['nama_media' => 'Media'],
            ['nama_media' => 'kertas koran'],
            ['nama_media' => 'usb'],
        ]);

        // master_pencipta
        $this->db->table('master_pencipta')->insertBatch([
            ['nama_pencipta' => 'Bidang Kepegawaian'],
            ['nama_pencipta' => 'Bidang Pengadaan'],
            ['nama_pencipta' => 'Bidang Hukum dan Tata Laksana'],
            ['nama_pencipta' => 'Bidang Keuangan'],
            ['nama_pencipta' => 'Bidang Umum dan Rumah Tangga'],
            ['nama_pencipta' => 'Bidang Produksi'],
            ['nama_pencipta' => 'Pencipta'],
            ['nama_pencipta' => 'Bidang ZZZ'],
            ['nama_pencipta' => 'Bidang QWE'],
        ]);

        // master_pengolah
        $this->db->table('master_pengolah')->insertBatch([
            ['nama_pengolah' => 'Unit Arsip Teknologi Informasi'],
            ['nama_pengolah' => 'Unit Arsip Kepegawaian'],
            ['nama_pengolah' => 'Unit Kearsipan Pusat'],
            ['nama_pengolah' => 'Unit Arsip Sekretariat Hukum dan Tata Laksana'],
            ['nama_pengolah' => 'Unit Arsip Pengadaan'],
            ['nama_pengolah' => 'Unit Arsip Biro Umum dan Rumah Tangga'],
            ['nama_pengolah' => 'Pengolah'],
            ['nama_pengolah' => 'unit ABC'],
            ['nama_pengolah' => 'Unit SDF'],
        ]);

        // master_user (default admin/user)
        $this->db->table('master_user')->insertBatch([
            [
                'username'    => 'admin',
                'password'    => password_hash('admin', PASSWORD_BCRYPT),
                'tipe'        => 'admin',
                'akses_klas'  => '',
                'akses_modul' => json_encode([
                    'entridata'    => 'on',
                    'sirkulasi'    => 'on',
                    'klasifikasi'  => 'on',
                    'pencipta'     => 'on',
                    'pengolah'     => 'on',
                    'lokasi'       => 'on',
                    'media'        => 'on',
                    'user'         => 'on',
                    'import'       => 'on',
                ]),
            ],
            [
                'username'    => 'user',
                'password'    => password_hash('user', PASSWORD_BCRYPT),
                'tipe'        => 'user',
                'akses_klas'  => 'sdm,hkp',
                'akses_modul' => json_encode([
                    'sirkulasi' => 'on',
                ]),
            ],
        ]);
    }
}
