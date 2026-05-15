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
      <a class="navbar-brand" href="<?= site_url('master/klas') ?>">Data Klasifikasi</a>
    </div>
    <form class="navbar-form navbar-left width-half-full" method="get" action="<?= site_url('master/klas') ?>">
      <div class="input-group width-full">
        <input type="text" name="katakunci" class="form-control" placeholder="kata kunci nama/kode" value="<?= esc($katakunci) ?>" /><span class="input-group-btn">
        <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i></button></span>
      </div>
    </form>

    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <?php if (hasModuleAccess('klasifikasi')): ?>
        <li><a href="#" data-toggle="modal" data-target="#addkode"><i class="glyphicon glyphicon-plus"></i> Entry Data Baru</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="row">
  <div class="col-md-12" id="divtabelkode">
    <table class="table table-bordered" name="vkode" id="vkode">
      <thead>
        <th>Kode</th>
        <th>Nama</th>
        <th>Retensi</th>
        <th class="width-sm"></th>
        <th class="width-sm"></th>
      </thead>
      <?php if (!empty($items)): ?>
        <?php $no = 1; ?>
        <?php foreach ($items as $u): ?>
          <tr>
            <td><?= esc($u['kode']) ?></td>
            <td><?= esc($u['nama']) ?></td>
            <td><?= esc($u['retensi']) ?> Tahun</td>
            <td><a data-toggle="modal" data-target="#editkode" class="edkode" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>
            <td><a data-toggle="modal" data-target="#delkode" class="delkode" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>
          </tr>
          <?php $no++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addkode">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Tambah Klasifikasi</h4>
      </div>
      <div class="modal-body">
        <form id="faddkode" class="form-horizontal" role="form" method="post" action="<?= site_url('master/klas/create') ?>">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="kode">Kode</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="adkode" name="kode" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="nama">Nama</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="nama" name="nama" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="retensi">Retensi</label>
            <div class="col-sm-10">
              <div class="input-group">
                <input type="text" class="form-control" id="retensi" name="retensi" />
                <span class="input-group-addon">Tahun</span>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="addkodego">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editkode">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Edit Klasifikasi</h4>
      </div>
      <div class="modal-body">
        <form id="fedkode" class="form-horizontal" role="form" method="post" action="<?= site_url('master/klas/update') ?>">
          <input type="hidden" name="id" id="edidkode" value="">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="kode">Kode</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="ekode" name="kode" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="nama">Nama</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="enama" name="nama" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="retensi">Retensi</label>
            <div class="col-sm-10">
              <div class="input-group">
                <input type="text" class="form-control" id="eretensi" name="retensi" />
                <span class="input-group-addon">Tahun</span>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="editkodego">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="delkode">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Delete Klasifikasi</h4>
      </div>
      <div class="modal-body">
        <form id="fdelkode" class="form-horizontal" role="form" method="post" action="<?= site_url('master/klas/delete') ?>">
          <h4 class="modal-title">Yakin ingin Hapus data ini?</h4>
          <input type="hidden" name="id" id="delidkode" value="">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="delkodego">Hapus</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>