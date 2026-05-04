<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArteriTables extends Migration
{
    public function up(): void
    {
        // master_kode
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kode'        => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'nama'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'retensi'     => ['type' => 'INT', 'constraint' => 11, 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode');
        $this->forge->addKey('nama');
        $this->forge->createTable('master_kode', true);

        // master_lokasi
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_lokasi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama_lokasi');
        $this->forge->createTable('master_lokasi', true);

        // master_media
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_media'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama_media');
        $this->forge->createTable('master_media', true);

        // master_pencipta
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_pencipta' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama_pencipta');
        $this->forge->createTable('master_pencipta', true);

        // master_pengolah
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'nama_pengolah'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('nama_pengolah');
        $this->forge->createTable('master_pengolah', true);

        // master_user
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'password'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tipe'          => ['type' => 'ENUM', 'constraint' => ['admin', 'user'], 'null' => false],
            'akses_klas'    => ['type' => 'TEXT', 'null' => false],
            'akses_modul'   => ['type' => 'TEXT', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('master_user', true);

        // data_arsip
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'noarsip'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'pencipta'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'unit_pengolah' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tanggal'       => ['type' => 'DATE', 'null' => false],
            'uraian'        => ['type' => 'TEXT', 'null' => false],
            'ket'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'kode'          => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'jumlah'        => ['type' => 'INT', 'constraint' => 11, 'null' => false],
            'nobox'         => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => false],
            'lokasi'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'media'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'file'          => ['type' => 'TEXT', 'null' => true],
            'tgl_input'     => ['type' => 'DATETIME', 'null' => true],
            'tgl_update'    => ['type' => 'DATETIME', 'null' => true],
            'username'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('noarsip');
        $this->forge->addKey('pencipta');
        $this->forge->addKey('unit_pengolah');
        $this->forge->addKey('kode');
        $this->forge->addKey('lokasi');
        $this->forge->addKey('media');
        $this->forge->createTable('data_arsip', true);

        // sirkulasi
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'noarsip'            => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'username_peminjam'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'keperluan'          => ['type' => 'TEXT', 'null' => true],
            'tgl_pinjam'         => ['type' => 'DATETIME', 'null' => false],
            'tgl_haruskembali'   => ['type' => 'DATETIME', 'null' => false],
            'tgl_pengembalian'   => ['type' => 'DATETIME', 'null' => true],
            'tgl_transaksi'      => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('noarsip');
        $this->forge->addKey('username_peminjam');
        $this->forge->addKey('tgl_pinjam');
        $this->forge->addKey('tgl_pengembalian');
        $this->forge->addKey('tgl_haruskembali');
        $this->forge->createTable('sirkulasi', true);

        // system_log
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'kode_transaksi'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'username_transaksi' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'tgl_transaksi'      => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('kode_transaksi');
        $this->forge->addKey('username_transaksi');
        $this->forge->addKey('tgl_transaksi');
        $this->forge->createTable('system_log', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('system_log', true);
        $this->forge->dropTable('sirkulasi', true);
        $this->forge->dropTable('data_arsip', true);
        $this->forge->dropTable('master_user', true);
        $this->forge->dropTable('master_pengolah', true);
        $this->forge->dropTable('master_pencipta', true);
        $this->forge->dropTable('master_media', true);
        $this->forge->dropTable('master_lokasi', true);
        $this->forge->dropTable('master_kode', true);
    }
}
