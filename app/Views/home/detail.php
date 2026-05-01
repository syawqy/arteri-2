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
          <li><a href="<?= site_url('/admin/vedit/' . $id) ?>"><i class="glyphicon glyphicon-pencil"></i> Edit Arsip</a></li>
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
  <label class="col-md-6 isi"><?= (empty($file) ? '' : '<a href="' . base_url('files/' . $file) . '" target="_blank">' . esc($file) . '</a>') ?></label>
</div>

<div class="view-group row">
  <label class="col-md-6 control-label" for="nobox">Nama penginput</label>
  <label class="col-md-6 isi"><?= esc($username) ?></label>
</div>

</div><!-- /2nd column -->
</div><!-- /.row -->
