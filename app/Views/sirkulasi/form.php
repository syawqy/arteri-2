<?php
$actionUrl = $isEdit
    ? site_url('/sirkulasi/update/' . esc($id))
    : site_url('/sirkulasi');

$noarsipVal           = $isEdit ? esc($noarsip ?? '') : '';
$usernamePeminjamVal  = $isEdit ? esc($username_peminjam ?? '') : '';
$keperluanVal         = $isEdit ? esc($keperluan ?? '') : '';
$tglPinjamVal         = $isEdit ? esc($tgl_pinjam ?? '') : ($now ?? '');
$tglHarusKembaliVal   = $isEdit ? esc($tgl_haruskembali ?? '') : '';
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
            <a class="navbar-brand" href="#"><?= $isEdit ? 'Update Data Peminjaman' : 'Peminjaman Arsip' ?></a>
        </div>

        <div class="collapse navbar-collapse" id="module-submenu">
            <ul class="nav navbar-nav navbar-right">
                <li><a href="#" class="trigger-submit"><i class="glyphicon glyphicon-save"></i> Simpan</a></li>
                <li><a href="<?= site_url('/sirkulasi') ?>"><i class="glyphicon glyphicon-search"></i> Data Peminjaman</a></li>
            </ul>
        </div>
    </div>
</nav>

<form class="form-horizontal" data-toggle="validator" action="<?= $actionUrl ?>" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>

    <div class="row">
        <div class="col-md-12">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= esc($id) ?>">
                <?php if (! empty($previous)): ?>
                    <input type="hidden" name="previous" value="<?= esc($previous) ?>">
                <?php endif; ?>
            <?php endif; ?>

            <div class="form-group">
                <label class="col-md-2 control-label" for="noarsip">Nomor Arsip</label>
                <div class="col-md-8">
                    <input type="text" id="snoarsip" name="noarsip" value="<?= $noarsipVal ?>" class="form-control xhr"
                        placeholder="Ketikan 3 huruf/angka pertama kode arsip atau klasifikasi arsip"
                        data-xhr="<?= site_url('/ajax/arsip') ?>" autocomplete="off" required />
                </div>
                <div class="col-md-2">
                    <button id="singlebutton" name="singlebutton" type="submit" class="btn btn-success"><i class="glyphicon glyphicon-save"></i> Simpan</button>
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-2 control-label" for="username_peminjam">Username Peminjam</label>
                <div class="col-md-8">
                    <input type="text" id="username_peminjam" name="username_peminjam" value="<?= $usernamePeminjamVal ?>" class="form-control xhr"
                        placeholder="Ketikan 3 huruf pertama username yang akan meminjam"
                        data-xhr="<?= site_url('/ajax/user') ?>" autocomplete="off" required />
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-2 control-label" for="keperluan">Alasan keperluan peminjaman</label>
                <div class="col-md-8">
                    <textarea id="keperluan" name="keperluan" class="form-control" rows="3" required><?= $keperluanVal ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-2 control-label" for="tgl_pinjam">Tanggal Peminjaman</label>
                <div class="col-md-8">
                    <div class="input-group">
                        <div class="input-group-addon">(yyyy-mm-dd)</div>
                        <input id="tgl_pinjam" name="tgl_pinjam" class="form-control input-md" type="text" value="<?= $tglPinjamVal ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-md-2 control-label" for="tgl_haruskembali">Tanggal Harus Kembali</label>
                <div class="col-md-8">
                    <div class="input-group">
                        <div class="input-group-addon">(yyyy-mm-dd)</div>
                        <input id="tgl_haruskembali" name="tgl_haruskembali" class="form-control input-md" type="text" value="<?= $tglHarusKembaliVal ?>" required>
                    </div>
                </div>
            </div>

        </div>
    </div>

</form>
