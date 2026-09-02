<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<nav class="navbar navbar-inverse navbar-submenu">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#module-submenu" aria-expanded="false">
      </button>
      <a class="navbar-brand" href="#">Data Arsip</a>
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <?php if (hasModuleAccess('entridata')): ?>
          <li><a href="<?= site_url('/arsip/edit/' . $id) ?>"><i class="glyphicon glyphicon-pencil"></i> Edit Arsip</a></li>
        <?php endif; ?>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<!-- Form Name -->
<div class="row">
<div class="col-md-6"> <!-- 1st column -->

<div class="view-group row">
  <label class="col-md-6 control-label" for="noarsip">Nomor Arsip</label>
  <label class="col-md-6 isi"><?= esc($noarsip) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="tanggal">Tanggal Penciptaan</label>
  <label class="col-md-6 isi"><?= date_format(date_create($tanggal), 'd-M-Y') ?>
    <?php if ($f === 'sudah'): ?>
      <br /><b>Retensi Sudah Lewat : <?= date_format(date_create($b), 'd-M-Y') ?></b>
    <?php else: ?>
      <br />Retensi tanggal : <?= date_format(date_create($b), 'd-M-Y') ?>
    <?php endif; ?>
  </label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="pencipta">Pencipta Arsip</label>
  <label class="col-md-6 isi"><?= esc($nama_pencipta) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="unitpengolah">Unit Pengolah</label>
  <label class="col-md-6 isi"><?= esc($nama_pengolah) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="kode">Kode Klasifikasi</label>
  <label class="col-md-6 isi"><?= esc($nama_kode) ?> - <?= esc($nama) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="uraian">Uraian</label>
  <label class="col-md-6 isi"><?= esc($uraian) ?></label>
</div>

</div><!-- /1st column -->

<div class="col-md-6"><!-- 2nd column -->
<div class="view-group row">
  <label class="col-md-6 control-label" for="lokasi">Lokasi Arsip</label>
  <label class="col-md-6 isi"><?= esc($nama_lokasi) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="media">Jenis Media</label>
  <label class="col-md-6 isi"><?= esc($nama_media) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="ket">Keterangan Keaslian</label>
  <label class="col-md-6 isi"><?= esc($ket) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="jumlah">Jumlah</label>
  <label class="col-md-6 isi"><?= esc($jumlah) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="nobox">Nomor Box</label>
  <label class="col-md-6 isi"><?= esc($nobox) ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="nobox">File</label>
  <label class="col-md-6 isi"><?= (empty($file) ? '' : '<a href="' . site_url('file/' . $file) . '" target="_blank">' . esc($file) . '</a>') ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="nobox">Nama penginput</label>
  <label class="col-md-6 isi"><?= esc($username) ?></label>
</div>

</div><!-- /2nd column -->
</div><!-- /row -->

<!-- Contextual Discovery / RiC Network Panel -->
<div class="row" style="margin-top: 30px;">
  <div class="col-md-12">
    <div class="panel panel-info">
      <div class="panel-heading">
        <h3 class="panel-title">
          <i class="glyphicon glyphicon-link"></i> <strong>Jejaring Berkas Terkait (ICA Records in Contexts / RiC-CM)</strong>
        </h3>
      </div>
      <div class="panel-body">
        <p class="text-muted" style="margin-bottom: 15px;">
          <small>Ditemukan secara kontekstual berdasarkan afinitas Unit Pencipta/Pengolah (<em>Agent</em>), Urusan/Klasifikasi (<em>Activity</em>), dan Kedekatan Kurun Waktu (<em>Temporal Proximity</em>).</small>
        </p>

        <?php if (!empty($related_ric)): ?>
          <div class="table-responsive">
            <table class="table table-hover table-bordered table-striped">
              <thead>
                <tr class="active">
                  <th style="width: 15%;">No. Arsip</th>
                  <th>Uraian Informasi</th>
                  <th style="width: 15%;">Klasifikasi & Pencipta</th>
                  <th style="width: 12%;">Tanggal</th>
                  <th style="width: 18%;">Skor Afinitas (CAS)</th>
                  <th style="width: 10%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($related_ric as $rel): ?>
                  <tr>
                    <td><strong><?= esc($rel['noarsip']) ?></strong></td>
                    <td><?= esc($rel['uraian']) ?></td>
                    <td>
                      <span class="label label-primary"><?= esc($rel['kode']) ?></span><br/>
                      <small><?= esc($rel['pencipta']) ?></small>
                    </td>
                    <td><?= !empty($rel['tanggal']) ? date_format(date_create($rel['tanggal']), 'd-M-Y') : '-' ?></td>
                    <td>
                      <div class="progress" style="margin-bottom: 4px; height: 16px;">
                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" style="width: <?= round($rel['cas_score'] * 100) ?>%;">
                          <?= round($rel['cas_score'] * 100) ?>%
                        </div>
                      </div>
                      <small class="text-muted">
                        Agent: <?= $rel['agent_affinity'] * 100 ?>% | Act: <?= $rel['activity_affinity'] * 100 ?>%
                      </small>
                    </td>
                    <td>
                      <a href="<?= site_url('/arsip/detail/' . $rel['id']) ?>" class="btn btn-xs btn-info">
                        <i class="glyphicon glyphicon-eye-open"></i> Buka Berkas
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="alert alert-warning" style="margin-bottom: 0;">
            <i class="glyphicon glyphicon-info-sign"></i> Belum ditemukan jejaring berkas terkait untuk arsip ini.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row">
<div class="col-md-12">
<a href="<?= site_url('/home') ?>" class="btn btn-default"><i class="glyphicon glyphicon-arrow-left"></i> Kembali ke Pencarian</a>
</div>
</div>
<?= $this->endSection() ?>
