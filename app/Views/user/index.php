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
      <a class="navbar-brand" href="<?= site_url('user') ?>">Data User</a>
    </div>
    <form class="navbar-form navbar-left width-half-full" method="get" action="<?= site_url('user') ?>">
      <div class="input-group width-full">
        <input type="text" name="katakunci" class="form-control" placeholder="kata kunci username" value="<?= esc($katakunci) ?>"/><span class="input-group-btn">
        <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i></button></span>
      </div>
    </form>

    <div class="collapse navbar-collapse" id="module-submenu">
      <ul class="nav navbar-nav navbar-right">
        <?php if (hasModuleAccess('user')): ?>
        <li><a href="#" data-toggle="modal" data-target="#adduser"><i class="glyphicon glyphicon-plus"></i> Entry User Baru</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="row">
  <div class="col-md-12" id="divtabeluser">
    <table class="table table-bordered" name="vuser" id="vuser">
      <thead>
        <th class="width-sm">No</th>
        <th>Username</th>
        <th>Akses Klasifikasi</th>
        <th>Akses Modul</th>
        <th>Tipe</th>
        <th class="width-sm"></th>
        <th class="width-sm"></th>
      </thead>
      <?php if (!empty($users)): ?>
        <?php $no = 1; ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= $no ?></td>
            <td><?= esc($u['username']) ?></td>
            <td><?= esc($u['akses_klas']) ?></td>
            <td>
              <?php
                $mm = $u['akses_modul'];
                if ($mm !== '') {
                    $decoded = json_decode($mm, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $key => $val) {
                            echo esc($key) . ',';
                        }
                    }
                }
              ?>
            </td>
            <td><?= esc($u['tipe']) ?></td>
            <?php if (hasModuleAccess('user')): ?>
            <td><a data-toggle="modal" data-target="#edituser" class="eduser" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>
            <td><a data-toggle="modal" data-target="#deluser" class="deluser" href="#" id="<?= esc($u['id'], 'attr') ?>" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>
            <?php else: ?>
            <td></td><td></td>
            <?php endif; ?>
          </tr>
          <?php $no++; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="adduser">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Tambah User</h4>
      </div>
      <div class="modal-body">
        <form id="fadduser" class="form-horizontal" role="form" method="post" action="<?= site_url('user') ?>">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="username">username</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="username" name="username" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="password">password</label>
            <div class="col-sm-10">
              <input type="password" class="form-control" id="password" name="password" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="conf_password">Konfirmasi password</label>
            <div class="col-sm-10">
              <input type="password" class="form-control" id="conf_password" name="conf_password" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="akses_klas">Hak Akses Klasifikasi</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="akses_klas" name="akses_klas" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="modul">Hak Akses Modul</label>
            <div class="col-sm-10">
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul1" name="modul[entridata]">
                Entri Data
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul2" name="modul[sirkulasi]">
                Sirkulasi
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul3" name="modul[klasifikasi]">
                Klasifikasi
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul4" name="modul[pencipta]">
                Pencipta Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul5" name="modul[pengolah]">
                Pengolah Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul6" name="modul[lokasi]">
                Lokasi Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul7" name="modul[media]">
                Media Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul8" name="modul[user]">
                User
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="modul9" name="modul[import]">
                Import Data
              </label>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="tipe">Tipe</label>
            <div class="col-sm-10">
              <select id="tipe" name="tipe" class="form-control">
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="addusergo">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="edituser">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Edit User</h4>
      </div>
      <div class="modal-body">
        <form id="feduser" class="form-horizontal" role="form" method="post" action="<?= site_url('user/update') ?>">
          <input type="hidden" name="id" id="ediduser" value="">
          <div class="form-group">
            <label class="col-sm-2 control-label" for="eusername">username</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="eusername" name="username" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="epassword">password</label>
            <div class="col-sm-10">
              <input type="password" class="form-control" id="epassword" name="password" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="eakses_klas">Hak Akses Klasifikasi</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="eakses_klas" name="akses_klas" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="emodul">Hak Akses Modul</label>
            <div class="col-sm-10">
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul1" name="modul[entridata]">
                Entri Data
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul2" name="modul[sirkulasi]">
                Sirkulasi
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul3" name="modul[klasifikasi]">
                Klasifikasi
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul4" name="modul[pencipta]">
                Pencipta Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul5" name="modul[pengolah]">
                Pengolah Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul6" name="modul[lokasi]">
                Lokasi Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul7" name="modul[media]">
                Media Arsip
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul8" name="modul[user]">
                User
              </label>
              <label class="form-check-label">
                <input class="form-check-input" type="checkbox" id="emodul9" name="modul[import]">
                Import Data
              </label>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="etipe">Tipe</label>
            <div class="col-sm-10">
              <select id="etipe" name="tipe" class="form-control">
                <option value="admin">Admin</option>
                <option value="user">User</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="editusergo">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deluser">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">Delete User</h4>
      </div>
      <div class="modal-body">
        <form id="fdeluser" class="form-horizontal" role="form" method="post" action="<?= site_url('user/delete') ?>">
          <h4 class="modal-title">Yakin ingin Hapus data ini?</h4>
          <input type="hidden" name="id" id="deliduser" value="">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="delusergo">Hapus</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>