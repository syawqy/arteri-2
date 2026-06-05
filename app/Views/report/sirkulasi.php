<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<?php
/**
 * Laporan Sirkulasi View
 */
?>

<!-- Page Header -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-refresh"></i> Laporan Sirkulasi
        </h1>
    </div>
</div>

<!-- Filter Form -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-filter"></i> Filter Laporan
            </div>
            <div class="panel-body">
                <form method="GET" action="<?= site_url('report/sirkulasi') ?>" class="form-inline">
                    <div class="form-group">
                        <label for="username">Peminjam:</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?= esc($filters['username'] ?? '') ?>" placeholder="Nama peminjam">
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">-- Semua --</option>
                            <option value="dipinjam" <?= ($filters['status'] ?? '') === 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                            <option value="dikembalikan" <?= ($filters['status'] ?? '') === 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
                            <option value="overdue" <?= ($filters['status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_from">Dari Tanggal:</label>
                        <input type="date" class="form-control" id="tanggal_from" name="tanggal_from"
                               value="<?= esc($filters['tanggal_from'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="tanggal_to">Sampai Tanggal:</label>
                        <input type="date" class="form-control" id="tanggal_to" name="tanggal_to"
                               value="<?= esc($filters['tanggal_to'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="glyphicon glyphicon-search"></i> Tampilkan
                    </button>
                    <a href="<?= site_url('report/sirkulasi') ?>" class="btn btn-default">
                        <i class="glyphicon glyphicon-refresh"></i> Reset
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Report Actions -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-body text-right">
                <?php
                $query     = http_build_query($filters);
                $exportUrl = site_url('report/sirkulasi/export-excel?' . $query);
                $printUrl  = site_url('report/sirkulasi/print?' . $query);
                ?>
                <a href="<?= $exportUrl ?>" class="btn btn-success js-export">
                    <i class="glyphicon glyphicon-file"></i> Export Excel
                </a>
                <a href="<?= $printUrl ?>" class="btn btn-danger" target="_blank">
                    <i class="glyphicon glyphicon-print"></i> Export PDF
                </a>
                <a href="<?= site_url('report') ?>" class="btn btn-default">
                    <i class="glyphicon glyphicon-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Results Table -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-list"></i> Hasil Laporan
                <span class="badge"><?= number_format($total ?? 0) ?> data</span>
            </div>
            <div class="panel-body">
                <?php if (! empty($results)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>No.Arsip</th>
                                    <th>Uraian</th>
                                    <th>Peminjam</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Tgl Harus Kembali</th>
                                    <th>Tgl Kembali</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($results as $row):
                                    $status = is_null($row['tgl_pengembalian']) ? 'Dipinjam' : 'Dikembalikan';
                                    $statusClass = is_null($row['tgl_pengembalian']) ? 'label-warning' : 'label-success';
                                    if (is_null($row['tgl_pengembalian']) && $row['tgl_haruskembali'] < date('Y-m-d H:i:s')) {
                                        $status = 'Overdue';
                                        $statusClass = 'label-danger';
                                    }
                                ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($row['noarsip'] ?? '-') ?></td>
                                        <td><?= esc(substr($row['uraian'] ?? '-', 0, 50)) ?><?= strlen($row['uraian'] ?? '') > 50 ? '...' : '' ?></td>
                                        <td><?= esc($row['username'] ?? '-') ?></td>
                                        <td><?= esc($row['tgl_pinjam'] ?? '-') ?></td>
                                        <td><?= esc($row['tgl_haruskembali'] ?? '-') ?></td>
                                        <td><?= esc($row['tgl_pengembalian'] ?? '-') ?></td>
                                        <td><span class="label <?= $statusClass ?>"><?= $status ?></span></td>
                                        <td><?= esc($row['ket'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="glyphicon glyphicon-warning-sign"></i>
                        Tidak ada data yang sesuai dengan filter yang dipilih.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>