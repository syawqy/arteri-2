<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<?php
/**
 * Report Index - Halaman Utama Laporan
 */
?>

<!-- Page Header -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-print"></i> Laporan
            <small>Sirkulasi & Arsip</small>
        </h1>
    </div>
</div>

<!-- Report Options -->
<div class="row">
    <!-- Laporan Arsip -->
    <div class="col-lg-6 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-folder-open" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div style="font-size: 18px; font-weight: bold;">Laporan Arsip</div>
                        <div>Daftar arsip berdasarkan filter</div>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <p>Lihat dan export laporan data arsip dengan filter berdasarkan klasifikasi, tanggal, dan kata kunci.</p>
                <a href="<?= site_url('report/arsip') ?>" class="btn btn-primary btn-block">
                    <i class="glyphicon glyphicon-list-alt"></i> Lihat Laporan
                </a>
            </div>
        </div>
    </div>

    <!-- Laporan Sirkulasi -->
    <div class="col-lg-6 col-md-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-refresh" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div style="font-size: 18px; font-weight: bold;">Laporan Sirkulasi</div>
                        <div>Daftar peminjaman arsip</div>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <p>Lihat dan export laporan sirkulasi arsip dengan filter berdasarkan peminjam, status, dan tanggal.</p>
                <a href="<?= site_url('report/sirkulasi') ?>" class="btn btn-success btn-block">
                    <i class="glyphicon glyphicon-list-alt"></i> Lihat Laporan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Link -->
<div class="row">
    <div class="col-lg-12">
        <div class="alert alert-info">
            <i class="glyphicon glyphicon-info-sign"></i>
            <strong>TIP:</strong> Untuk melihat statistik cepat, silakan kunjungi
            <a href="<?= site_url('dashboard') ?>" class="alert-link">Halaman Dashboard</a>.
        </div>
    </div>
</div>
<?= $this->endSection() ?>