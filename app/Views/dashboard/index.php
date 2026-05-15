<?php
/**
 * Dashboard View - Statistik Arsip
 */
?>

<!-- Dashboard Header -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-dashboard"></i> Dashboard
            <small>Statistik Arsip</small>
        </h1>
    </div>
</div>

<!-- Summary Cards Row -->
<div class="row">
    <!-- Total Arsip Card -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-folder-open" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['total_arsip'] ?? 0) ?></div>
                        <div>Total Arsip</div>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('search') ?>">
                <div class="panel-footer">
                    <span class="pull-left">Lihat Detail</span>
                    <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>

    <!-- Sedang Dipinjam Card -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-random" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['sedang_dipinjam'] ?? 0) ?></div>
                        <div>Sedang Dipinjam</div>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('sirkulasi') ?>">
                <div class="panel-footer">
                    <span class="pull-left">Lihat Detail</span>
                    <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>

    <!-- Overdue Card -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-alert" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge"><?= number_format($stats['overdue'] ?? 0) ?></div>
                        <div>Arsip Overdue</div>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('sirkulasi?status=overdue') ?>">
                <div class="panel-footer">
                    <span class="pull-left">Lihat Detail</span>
                    <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Charts and Tables Row -->
<div class="row">
    <!-- Statistik Per Klasifikasi -->
    <div class="col-lg-6 col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-tag"></i> Statistik Per Klasifikasi
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stats['per_klasifikasi'])): ?>
                                <?php foreach ($stats['per_klasifikasi'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['kode'] ?? '-') ?></td>
                                        <td><?= esc($row['nama'] ?? '-') ?></td>
                                        <td class="text-right"><?= number_format($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Per Lokasi -->
    <div class="col-lg-6 col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-map-marker"></i> Statistik Per Lokasi
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Lokasi</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stats['per_lokasi'])): ?>
                                <?php foreach ($stats['per_lokasi'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['nama_lokasi'] ?? '-') ?></td>
                                        <td class="text-right"><?= number_format($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Second Row Charts -->
<div class="row">
    <!-- Statistik Per Media -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-film"></i> Statistik Per Media
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Media</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stats['per_media'])): ?>
                                <?php foreach ($stats['per_media'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['nama_media'] ?? '-') ?></td>
                                        <td class="text-right"><?= number_format($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Per Pencipta -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-home"></i> Statistik Per Pencipta
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Pencipta</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stats['per_pencipta'])): ?>
                                <?php foreach ($stats['per_pencipta'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['nama_pencipta'] ?? '-') ?></td>
                                        <td class="text-right"><?= number_format($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Status -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-ok-circle"></i> Statistik Status
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stats['per_ket'])): ?>
                                <?php foreach ($stats['per_ket'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['ket'] ?: 'Belum diisi') ?></td>
                                        <td class="text-right"><?= number_format($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Tidak ada data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aktivitas Bulanan -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-calendar"></i> Aktivitas Bulanan
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="text-right">Total Pinjaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($stats['aktivitas_bulanan'])): ?>
                                <?php foreach ($stats['aktivitas_bulanan'] as $row): ?>
                                    <tr>
                                        <td><?= esc($row['bulan'] ?? '-') ?></td>
                                        <td class="text-right"><?= number_format($row['total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">Tidak ada data aktivitas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>