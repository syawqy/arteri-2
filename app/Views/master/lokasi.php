<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<nav class="navbar navbar-inverse navbar-submenu">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#module-submenu" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?= site_url('master/lokasi') ?>">Data Lokasi Arsip</a>
    </div>
    <form class="navbar-form navbar-left width-half-full" method="get" action="<?= site_url('master/lokasi') ?>">
      <div class="input-group width-full">
        <input type="text" name="katakunci" class="form-control" placeholder="kata kunci nama/kode" value="<?= esc($katakunci) ?>" /><span class="input-group-btn">
        <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i></button></span>
      </div>
    </form>

    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <?php if (hasModuleAccess('lokasi')): ?>
        <li><a href="#" data-toggle="modal" data-target="#addlok"><i class="glyphicon glyphicon-plus"></i> Entry Data Baru</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="row">
  <div class="col-md-12" id="divtabellok">
    <table class="table table-bordered" name="vlok" id="vlok">
      <thead>
        <th class="width-sm">No</th>
        <th>Nama Lokasi</th>
        <th class="width-sm"></th>
        <th class="width-sm"></th>
      </thead>
      <?php if (!empty($items)): ?>
        <?php $no = 1; ?>
        <?php foreach ($items as $u): ?>
          <tr>
            <td><?= $no ?></td>
            <td><?= esc($u['nama_lokasi']) ?></td>
            <td><a data-toggle="modal" data-target="#editlok" class="edlok" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>
            <td><a data-toggle="modal" data-target="#dellok" class="dellok" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>
          </tr>
          <?php $no++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addlok">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Tambah Lokasi Arsip</h4>
      </div>
      <div class="modal-body">
        <form id="faddlok" class="form-horizontal" role="form" method="post" action="<?= site_url('master/lokasi/create') ?>">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="nama">Nama</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="nama" name="nama" />
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="addlokgo">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editlok">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Edit Lokasi Arsip</h4>
      </div>
      <div class="modal-body">
        <form id="fedlok" class="form-horizontal" role="form" method="post" action="<?= site_url('master/lokasi/update') ?>">
          <input type="hidden" name="id" id="edidlok" value="">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="nama">Nama</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="enama" name="nama" />
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="editlokgo">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="dellok">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Delete Lokasi Arsip</h4>
      </div>
      <div class="modal-body">
        <form id="fdellok" class="form-horizontal" role="form" method="post" action="<?= site_url('master/lokasi/delete') ?>">
          <h4 class="modal-title">Yakin ingin Hapus data ini?</h4>
          <input type="hidden" name="id" id="delidlok" value="">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="dellokgo">Hapus</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>