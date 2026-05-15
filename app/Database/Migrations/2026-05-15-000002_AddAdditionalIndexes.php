<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration untuk menambahkan index tambahan untuk optimasi query.
 * 
 * Task breakdown: 1b-1 s/d 1b-5 (Database Indexing)
 * Index sudah ada di migration awal, migration ini untuk memastikan
 * dan menambahkan index jika belum ada.
 */
class AddAdditionalIndexes extends Migration
{
    public function up(): void
    {
        // Additional index for data_arsip
        // Index pada noarsip sudah ada di migration awal
        // Tambahkan index composite untuk search optimization
        
        // Index untuk data_arsip.kode - sudah ada, konfirmasi dengan addKey
        // Index untuk data_arsip.noarsip - sudah ada
        
        // Tambahkan index pada username untuk tracking siapa yang input data
        if (!$this->db->tableExists('data_arsip')) {
            return;
        }
        
        $db = \Config\Database::connect();
        
        // Cek apakah index sudah ada, jika belum tambahkan
        // Index pada data_arsip.username untuk tracking
        try {
            $db->query("ALTER TABLE `data_arsip` ADD INDEX `idx_username` (`username`)");
        } catch (\Throwable $e) {
            // Index mungkin sudah ada, skip
        }
        
        // Index pada data_arsip.tanggal untuk date range queries
        try {
            $db->query("ALTER TABLE `data_arsip` ADD INDEX `idx_tanggal` (`tanggal`)");
        } catch (\Throwable $e) {
            // Index mungkin sudah ada, skip
        }
        
        // Index pada data_arsip.ket untuk filter status
        try {
            $db->query("ALTER TABLE `data_arsip` ADD INDEX `idx_ket` (`ket`)");
        } catch (\Throwable $e) {
            // Index mungkin sudah ada, skip
        }
        
        // Sirkulasi additional indexes
        // Index pada tgl_haruskembali untuk overdue check
        try {
            $db->query("ALTER TABLE `sirkulasi` ADD INDEX `idx_tgl_haruskembali` (`tgl_haruskembali`)");
        } catch (\Throwable $e) {
            // Index mungkin sudah ada, skip
        }
        
        // Index untuk keperluan search di sirkulasi
        try {
            $db->query("ALTER TABLE `sirkulasi` ADD INDEX `idx_keperluan` (`keperluan`(100))");
        } catch (\Throwable $e) {
            // Index mungkin sudah ada, skip
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        
        // Drop additional indexes
        try {
            $db->query("ALTER TABLE `data_arsip` DROP INDEX `idx_username`");
        } catch (\Throwable $e) {
            // Index tidak ada
        }
        
        try {
            $db->query("ALTER TABLE `data_arsip` DROP INDEX `idx_tanggal`");
        } catch (\Throwable $e) {
            // Index tidak ada
        }
        
        try {
            $db->query("ALTER TABLE `data_arsip` DROP INDEX `idx_ket`");
        } catch (\Throwable $e) {
            // Index tidak ada
        }
        
        try {
            $db->query("ALTER TABLE `sirkulasi` DROP INDEX `idx_tgl_haruskembali`");
        } catch (\Throwable $e) {
            // Index tidak ada
        }
        
        try {
            $db->query("ALTER TABLE `sirkulasi` DROP INDEX `idx_keperluan`");
        } catch (\Throwable $e) {
            // Index tidak ada
        }
    }
}