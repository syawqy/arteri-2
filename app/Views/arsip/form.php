<?php

/**
 * Helper: convert shorthand size (e.g. 2M, 1G) to bytes.
 */
if (! function_exists('_return_bytes')) {
    function _return_bytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[-1] ?? '');
        $num  = (int) $val;

        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }
}

/**
 * Return the effective max upload size in bytes.
 */
if (! function_exists('_max_file_upload_in_bytes')) {
    function _max_file_upload_in_bytes(): int
    {
        $maxUpload = _return_bytes(ini_get('upload_max_filesize'));
        $maxPost   = _return_bytes(ini_get('post_max_size'));
        $memoryLimit = _return_bytes(ini_get('memory_limit'));

        return min($maxUpload, $maxPost, $memoryLimit);
    }
}

$actionUrl   = $isEdit
    ? site_url('/arsip/update/' . esc($id))
    : site_url('/arsip');

$archiveId   = $isEdit ? esc($id) : '';
$noarsipVal  = $isEdit ? esc($noarsip ?? '') : '';
$tanggalVal  = $isEdit ? esc($tanggal ?? '') : '';
$penciptaVal = $isEdit ? ($pencipta ?? '') : '';
$unitpengolahVal = $isEdit ? ($unit_pengolah ?? '') : '';
$kodeVal     = $isEdit ? ($kode ?? '') : '';
$uraianVal   = $isEdit ? esc($uraian ?? '') : '';
$lokasiVal   = $isEdit ? ($lokasi ?? '') : '';
$mediaVal    = $isEdit ? ($media ?? '') : '';
$ketVal      = $isEdit ? ($ket ?? '') : 'asli';
$jumlahVal   = $isEdit ? esc($jumlah ?? '') : '1';
$noboxVal    = $isEdit ? esc($nobox ?? '') : '';
$fileVal     = $isEdit ? ($file ?? '') : '';
?>
<nav class="navbar navbar-inverse navbar-submenu">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#module-submenu" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?= site_url('/home/'); ?>">Entry Data Arsip</a>
    </div>

    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="#" class="trigger-submit"><i class="glyphicon glyphicon-save"></i> Simpan</a></li>
        <li><a href="<?= site_url('/home/'); ?>"><i class="glyphicon glyphicon-search"></i> Lihat Data Arsip</a></li>
      </ul>
    </div>
  </div>
</nav>

<form class="form-horizontal" data-toggle="validator" action="<?= $actionUrl ?>" method="post" enctype="multipart/form-data">
<?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= $archiveId ?>">
    <?php if (! empty($previous)): ?>
    <input type="hidden" name="previous" value="<?= esc($previous) ?>">
    <?php endif; ?>
<?php endif; ?>

<div class="row">
<div class="col-md-6"> <!-- 1st column -->

<div class="form-group">
    <label class="col-md-4 control-label" for="noarsip">Nomor Arsip</label>
    <div class="col-md-8">
    <input id="noarsip" name="noarsip" class="form-control input-md" type="text" value="<?= $noarsipVal ?>" required>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="tanggal">Tanggal Penciptaan</label>
    <div class="col-md-8">
    <div class="input-group">
        <div class="input-group-addon">(yyyy-mm-dd)</div>
        <input id="tanggal" name="tanggal" class="form-control input-md" type="text" value="<?= $tanggalVal ?>" required>
    </div>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="pencipta">Pencipta Arsip</label>
    <div class="col-md-8">
    <select id="pencipta" name="pencipta" class="form-control input-md chosen">
    <?php if (isset($pencipta2) && is_array($pencipta2)): ?>
        <?php foreach ($pencipta2 as $k): ?>
        <option value="<?= esc($k['id']) ?>"<?= ($penciptaVal == $k['id']) ? ' selected="selected"' : '' ?>><?= esc($k['nama_pencipta']) ?></option>
        <?php endforeach; ?>
    <?php elseif (isset($pencipta) && is_array($pencipta)): ?>
        <?php foreach ($pencipta as $k): ?>
        <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_pencipta']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
    </select>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="unitpengolah">Unit Pengolah</label>
    <div class="col-md-8">
    <select id="unitpengolah" name="unitpengolah" class="form-control input-md chosen">
    <?php if (isset($unitpengolah2) && is_array($unitpengolah2)): ?>
        <?php foreach ($unitpengolah2 as $k): ?>
        <option value="<?= esc($k['id']) ?>"<?= ($unitpengolahVal == $k['id']) ? ' selected="selected"' : '' ?>><?= esc($k['nama_pengolah']) ?></option>
        <?php endforeach; ?>
    <?php elseif (isset($unitpengolah) && is_array($unitpengolah)): ?>
        <?php foreach ($unitpengolah as $k): ?>
        <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_pengolah']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
    </select>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="kode">Kode Klasifikasi</label>
    <div class="col-md-8">
    <select id="kode" name="kode" class="form-control input-md chosen">
    <?php if (isset($kode2) && is_array($kode2)): ?>
        <?php foreach ($kode2 as $k): ?>
        <option value="<?= esc($k['id']) ?>"<?= ($kodeVal == $k['id']) ? ' selected="selected"' : '' ?>><?= esc($k['nama']) ?> - <?= esc($k['kode']) ?></option>
        <?php endforeach; ?>
    <?php elseif (isset($kode) && is_array($kode)): ?>
        <?php foreach ($kode as $k): ?>
        <option value="<?= esc($k['id']) ?>"><?= esc($k['nama']) ?> - <?= esc($k['kode']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
    </select>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="uraian">Uraian</label>
    <div class="col-md-8">
    <textarea id="uraian" name="uraian" class="form-control" rows="3"><?= $uraianVal ?></textarea>
    </div>
</div>

</div><!-- /1st column -->

<div class="col-md-6"><!-- 2nd column -->

<div class="form-group">
    <label class="col-md-4 control-label" for="lokasi">Lokasi Arsip</label>
    <div class="col-md-8">
    <select id="lokasi" name="lokasi" class="form-control input-md chosen">
    <?php if (isset($lokasi2) && is_array($lokasi2)): ?>
        <?php foreach ($lokasi2 as $k): ?>
        <option value="<?= esc($k['id']) ?>"<?= ($lokasiVal == $k['id']) ? ' selected="selected"' : '' ?>><?= esc($k['nama_lokasi']) ?></option>
        <?php endforeach; ?>
    <?php elseif (isset($lokasi) && is_array($lokasi)): ?>
        <?php foreach ($lokasi as $k): ?>
        <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_lokasi']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
    </select>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="media">Jenis Media</label>
    <div class="col-md-8">
    <select id="media" name="media" class="form-control input-md chosen">
    <?php if (isset($media2) && is_array($media2)): ?>
        <?php foreach ($media2 as $k): ?>
        <option value="<?= esc($k['id']) ?>"<?= ($mediaVal == $k['id']) ? ' selected="selected"' : '' ?>><?= esc($k['nama_media']) ?></option>
        <?php endforeach; ?>
    <?php elseif (isset($media) && is_array($media)): ?>
        <?php foreach ($media as $k): ?>
        <option value="<?= esc($k['id']) ?>"><?= esc($k['nama_media']) ?></option>
        <?php endforeach; ?>
    <?php endif; ?>
    </select>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="ket">Keterangan Keaslian</label>
    <div class="col-md-8">
    <select class="form-control" name="ket" id="ket">
        <option value="asli" <?= ($ketVal === 'asli') ? 'selected="selected"' : '' ?>>Asli</option>
        <option value="copy" <?= ($ketVal === 'copy') ? 'selected="selected"' : '' ?>>Copy</option>
    </select>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="jumlah">Jumlah</label>
    <div class="col-md-8">
    <input id="jumlah" name="jumlah" class="form-control input-md" type="text" value="<?= $jumlahVal ?>">
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="nobox">Nomor Box</label>
    <div class="col-md-8">
    <input id="nobox" name="nobox" class="form-control input-md" type="text" value="<?= $noboxVal ?>">
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="file">File</label>
    <div class="col-md-8">
    <?php if ($isEdit && $fileVal !== ''): ?>
        <span style="text-overflow:ellipsis;overflow:hidden;" id="linkfile" class="form-control">
            <a href="<?= site_url('file/' . esc($fileVal)) ?>"><?= esc($fileVal) ?></a>
        </span>
        <span class="pull-right">
            <a href="#" data-toggle="modal" data-target="#delfile">
                <span class="glyphicon glyphicon-remove" style="color:red" aria-hidden="true"></span>
            </a>
        </span>
        <div id="uplodfile" style="display:none;">
    <?php else: ?>
        <div id="uplodfile">
    <?php endif; ?>
        <input type="file" id="file" name="file" accept=".doc,.docx,.pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/pdf">
        <p class="help-block">Ukuran Maksimal <?= number_format(ceil(_max_file_upload_in_bytes() / 1000)) ?> KB</p>
        </div>
    </div>
</div>

<div class="form-group">
    <label class="col-md-4 control-label" for="singlebutton"></label>
    <div class="col-md-8">
    <button id="singlebutton" name="singlebutton" type="submit" class="btn btn-success">Simpan</button>
    </div>
</div>

</div><!-- /2nd column -->
</div><!-- /.row -->

</form>

<?php if ($isEdit && $fileVal !== ''): ?>
<!-- Delete file modal -->
<div class="modal fade" id="delfile">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Delete File</h4>
      </div>
      <div class="modal-body">
        <form id="fdelfile" class="form-horizontal" role="form" method="post" action="<?= site_url('/arsip/delfile/' . $archiveId) ?>">
            <h4 class="modal-title">Yakin ingin Hapus File ini?</h4>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="delfilego">Hapus</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
