<nav class="navbar navbar-inverse navbar-submenu">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#module-submenu" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="<?= site_url('master/pengolah') ?>">Data Unit Pengolah Arsip</a>
    </div>
    <form class="navbar-form navbar-left width-half-full" method="get" action="<?= site_url('master/pengolah') ?>">
      <div class="input-group width-full">
        <input type="text" name="katakunci" class="form-control" placeholder="kata kunci nama/kode" value="<?= esc($katakunci) ?>" /><span class="input-group-btn">
        <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i></button></span>
      </div>
    </form>

    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <?php if (hasModuleAccess('pengolah')): ?>
        <li><a href="#" data-toggle="modal" data-target="#addpeng"><i class="glyphicon glyphicon-plus"></i> Entry Data Baru</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="row">
  <div class="col-md-12" id="divtabelpeng">
    <table class="table table-bordered" name="vpeng" id="vpeng">
      <thead>
        <th class="width-sm">No</th>
        <th>Nama Unit Pengolah</th>
        <th class="width-sm"></th>
        <th class="width-sm"></th>
      </thead>
      <?php if (!empty($items)): ?>
        <?php $no = 1; ?>
        <?php foreach ($items as $u): ?>
          <tr>
            <td><?= $no ?></td>
            <td><?= esc($u['nama_pengolah']) ?></td>
            <td><a data-toggle="modal" data-target="#editpeng" class="edpeng" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>
            <td><a data-toggle="modal" data-target="#delpeng" class="delpeng" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>
          </tr>
          <?php $no++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addpeng">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Tambah Pengolah Arsip</h4>
      </div>
      <div class="modal-body">
        <form id="faddpeng" class="form-horizontal" role="form" method="post" action="<?= site_url('master/pengolah/create') ?>">
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
        <button type="button" class="btn btn-primary" id="addpenggo">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editpeng">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Edit Pengolah Arsip</h4>
      </div>
      <div class="modal-body">
        <form id="fedpeng" class="form-horizontal" role="form" method="post" action="<?= site_url('master/pengolah/update') ?>">
          <input type="hidden" name="id" id="edidpeng" value="">
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
        <button type="button" class="btn btn-primary" id="editpenggo">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="delpeng">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Delete Pengolah Arsip</h4>
      </div>
      <div class="modal-body">
        <form id="fdelpeng" class="form-horizontal" role="form" method="post" action="<?= site_url('master/pengolah/delete') ?>">
          <h4 class="modal-title">Yakin ingin Hapus data ini?</h4>
          <input type="hidden" name="id" id="delidpeng" value="">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="delpenggo">Hapus</button>
      </div>
    </div>
  </div>
</div>
