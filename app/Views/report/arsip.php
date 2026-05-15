<?php
/**
 * Laporan Arsip View
 */
?>

<!-- Page Header -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-folder-open"></i> Laporan Arsip
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
                <form method="GET" action="<?= site_url('report/arsip') ?>" class="form-inline">
                    <div class="form-group">
                        <label for="katakunci">Kata Kunci:</label>
                        <input type="text" class="form-control" id="katakunci" name="katakunci" 
                               value="<?= esc($filters['katakunci'] ?? '') ?>" placeholder="Uraian arsip">
                    </div>
                    <div class="form-group">
                        <label for="kode">Klasifikasi:</label>
                        <select class="form-control chosen-select" id="kode" name="kode">
                            <option value="">-- Semua --</option>
                            <?php if (! empty($kode)): ?>
                                <?php foreach ($kode as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= ($filters['kode'] ?? '') == $k['id'] ? 'selected' : '' ?>>
                                        <?= esc($k['kode'] . ' - ' . $k['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ket">Status:</label>
                        <select class="form-control" id="ket" name="ket">
                            <option value="">-- Semua --</option>
                            <option value="asli" <?= ($filters['ket'] ?? '') === 'asli' ? 'selected' : '' ?>>Asli</option>
                            <option value="copy" <?= ($filters['ket'] ?? '') === 'copy' ? 'selected' : '' ?>>Copy</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="glyphicon glyphicon-search"></i> Tampilkan
                    </button>
                    <a href="<?= site_url('report/arsip') ?>" class="btn btn-default">
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
                $exportUrl = site_url('report/arsip/export-excel?' . http_build_query($filters));
                ?>
                <a href="<?= $exportUrl ?>" class="btn btn-success">
                    <i class="glyphicon glyphicon-file"></i> Export Excel
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
                                    <th>Tanggal</th>
                                    <th>Klasifikasi</th>
                                    <th>Uraian</th>
                                    <th>Pencipta</th>
                                    <th>Pengolah</th>
                                    <th>Media</th>
                                    <th>Lokasi</th>
                                    <th>Ket</th>
                                    <th>Jumlah</th>
                                    <th>No.Box</th>
                                    <th>Retensi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($results as $row): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= esc($row['noarsip'] ?? '-') ?></td>
                                        <td><?= esc($row['tanggal'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_kode'] ?? '-') ?></td>
                                        <td><?= esc($row['uraian'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_pencipta'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_pengolah'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_media'] ?? '-') ?></td>
                                        <td><?= esc($row['nama_lokasi'] ?? '-') ?></td>
                                        <td><?= esc($row['ket'] ?: '-') ?></td>
                                        <td><?= esc($row['jumlah'] ?? '-') ?></td>
                                        <td><?= esc($row['nobox'] ?? '-') ?></td>
                                        <td><?= esc($row['b'] ?? '-') ?></td>
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