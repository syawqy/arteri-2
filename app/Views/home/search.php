<nav class="navbar navbar-inverse navbar-submenu">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#module-submenu" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#">Data Arsip</a>
    </div>

    <form class="navbar-form navbar-left width-half-full" method="get" action="<?= site_url('/home/search') ?>">
      <div class="input-group width-full">
        <input type="text" name="katakunci" class="form-control" placeholder="nomor arsip/kata kunci uraian" value="<?= esc($src['katakunci'] ?? '') ?>" /><span class="input-group-btn">
          <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i></button></span>
      </div>
    </form>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="#" role="button" data-toggle="collapse" data-target="#advanced-search"
          aria-expanded="false" aria-controls="advanced-search"
          class="open-advanced-search"><i class="glyphicon glyphicon-search"></i> Pencarian Lanjut</a></li>
        <?php if (session('username')): ?>
        <li><a href="<?= site_url('/home/dl') . (service('request')->getUri()->getQuery() ? '?' . service('request')->getUri()->getQuery() : '') ?>"><i class="glyphicon glyphicon-download"></i> Download Data</a></li>
        <?php endif; ?>
      </ul>
    </div><!-- /.navbar-collapse -->
  </div><!-- /.container-fluid -->
</nav>

<?php if (session('zz')): ?>
  <div class="alert alert-info"><?= esc(session('zz')) ?></div>
<?php endif; ?>

<div class="panel panel-default panel-hidden collapse" id="advanced-search">
  <div class="panel-heading"><h3 class="panel-title">Pencarian Lanjut</h3></div>
  <div class="panel-body">
    <form action="<?= site_url('/home/search') ?>" method="get" id="srcmain">
      <div class="input-group">
        <span class="input-group-addon">Uraian</span>
        <input id="uraian" name="uraian" class="form-control input-md" type="text" value="<?= esc($src['uraian'] ?? '') ?>">
        <span class="input-group-addon">No Surat/Arsip</span>
        <input id="noarsip" name="noarsip" class="form-control input-md" type="text" value="<?= esc($src['noarsip'] ?? '') ?>">
        <span class="input-group-addon">Retensi</span>
        <select class="form-control" name="retensi" id="retensi">
          <option value="all">Semua</option>
          <option value="sudah"<?= (($src['retensi'] ?? '') === 'sudah' ? ' selected=selected' : '') ?>>Sudah</option>
          <option value="belum"<?= (($src['retensi'] ?? '') === 'belum' ? ' selected=selected' : '') ?>>Belum</option>
        </select>
      </div>
      <br/>
      <div class="input-group">
        <span class="input-group-addon">Pencipta arsip</span>
        <select class="form-control" name="penc" id="penc">
          <option value="all">Semua</option>
          <?php if (isset($penc)): ?>
            <?php foreach ($penc as $p): ?>
              <option value="<?= esc($p['id']) ?>"<?= (($src['penc'] ?? '') == $p['id'] ? ' selected=selected' : '') ?>><?= esc($p['nama_pencipta']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <span class="input-group-addon">Unit pengolah</span>
        <select class="form-control" name="peng" id="peng">
          <option value="all">Semua</option>
          <?php if (isset($peng)): ?>
            <?php foreach ($peng as $p): ?>
              <option value="<?= esc($p['id']) ?>"<?= (($src['peng'] ?? '') == $p['id'] ? ' selected=selected' : '') ?>><?= esc($p['nama_pengolah']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <span class="input-group-addon">Lokasi</span>
        <select class="form-control" name="lok" id="lok">
          <option value="all">Semua</option>
          <?php if (isset($lok)): ?>
            <?php foreach ($lok as $p): ?>
              <option value="<?= esc($p['id']) ?>"<?= (($src['lok'] ?? '') == $p['id'] ? ' selected=selected' : '') ?>><?= esc($p['nama_lokasi']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <span class="input-group-addon">Media</span>
        <select class="form-control" name="med" id="med">
          <option value="all">Semua</option>
          <?php if (isset($med)): ?>
            <?php foreach ($med as $p): ?>
              <option value="<?= esc($p['id']) ?>"<?= (($src['med'] ?? '') == $p['id'] ? ' selected=selected' : '') ?>><?= esc($p['nama_media']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
      </div>
      <br/>
      <div class="input-group">
        <span class="input-group-addon">Tanggal (yyyy-mm-dd)</span>
        <input id="tanggal" name="tanggal" class="form-control input-md" type="text" value="<?= esc($src['tanggal'] ?? '') ?>">
        <span class="input-group-addon">Ket</span>
        <select class="form-control" name="ket" id="ket">
          <option value="all">Semua</option>
          <option value="asli"<?= (($src['ket'] ?? '') === 'asli' ? ' selected=selected' : '') ?>>Asli</option>
          <option value="copy"<?= (($src['ket'] ?? '') === 'copy' ? ' selected=selected' : '') ?>>Copy</option>
        </select>
        <span class="input-group-addon">Kode Klasifikasi</span>
        <select class="form-control" name="kode" id="zkode">
          <option value="all">Semua</option>
          <?php if (isset($kode)): ?>
            <?php foreach ($kode as $p): ?>
              <option value="<?= esc($p['kode']) ?>"<?= (($src['kode'] ?? '') == $p['kode'] ? ' selected=selected' : '') ?>><?= esc($p['kode']) ?> - <?= esc($p['nama']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <span class="input-group-addon">No. Box</span>
        <input id="nobox" name="nobox" class="form-control input-md" type="text" value="<?= esc($src['nobox'] ?? '') ?>">
        <span class="input-group-btn">
          <button class="btn btn-primary" type="submit" id="go"> Cari</button>
        </span>
      </div>
    </form>
  </div>
  <!-- ./panel body -->
</div>
<!-- ./panel -->

<!-- Title -->
<div class="well well-sm">
  <div class="row">
    <div class="col-xs-9">Ditemukan data sebanyak : <em class='small'>(<?= number_format($jml) ?>)</em> arsip</div>
    <div class="col-xs-3 text-right"></div>
  </div>
</div>
<!-- /.row -->

<!-- Page Features -->
<div class="row" id="hslsrc">
  <table id="tblhslsrc" class="table table-bordered table-hover" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th>No Arsip</th>
        <th>Tanggal</th>
        <th>Kode Klasifikasi</th>
        <th>Uraian</th>
        <th>Ket</th>
        <th>File</th>
        <th>Jumlah</th>
        <th>No. Box</th>
        <th>Retensi</th>
        <th class="width-sm"></th>
        <th class="width-sm"></th>
        <th class="width-sm"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($data as $a): ?>
        <tr>
          <td><?= esc($a['noarsip']) ?></td>
          <td><?= esc($a['tanggal']) ?></td>
          <td><?= esc($a['nama_kode'] ?? '') ?></td>
          <td><?= esc($a['uraian']) ?></td>
          <td><?= esc($a['ket']) ?></td>
          <td><?php if ($a['file'] === ''): ?>
            <?php else: ?>
              <a href='<?= site_url('file/' . $a['file']) ?>' target='_blank'><span class='glyphicon glyphicon-save' aria-hidden='true'></span></a>
            <?php endif; ?>
          </td>
          <td><?= esc($a['jumlah']) ?></td>
          <td><?= esc($a['nobox']) ?></td>
          <td<?= ($a['f'] === 'sudah' ? ' class="danger"' : '') ?>><?= esc($a['b']) ?></td>
          <td><a href='<?= site_url("view/" . $a["id"]) ?>'><i class="glyphicon glyphicon-search"></i></a></td>
          <td>
            <?php if (hasModuleAccess('entridata')): ?>
              <a href='<?= site_url('/arsip/edit/' . $a['id']) ?>'><span class='glyphicon glyphicon-pencil' aria-hidden='true'></span></a>
            <?php endif; ?>
          </td>
          <td>
            <?php if (hasModuleAccess('entridata')): ?>
              <a class='deldata' id='<?= $a['id'] ?>' href='#' data-toggle="modal" data-target="#deldata"><i class="glyphicon glyphicon-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<!-- /.row -->

<?= $pages ?>

<div class="modal fade" id="deldata">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Delete Data</h4>
      </div>
      <div class="modal-body">
        <form id="fdeldata" class="form-horizontal" role="form" method="post" action="<?= site_url('/arsip/delete') ?>">
          <?= csrf_field() ?>
          <h4 class="modal-title">Yakin ingin Hapus Data ini?</h4>
          <input type="hidden" name="id" id="deliddata" value="">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="deldatago">Hapus</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
