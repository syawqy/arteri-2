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
            <a class="navbar-brand" href="<?= site_url('/sirkulasi') ?>">Data Sirkulasi</a>
        </div>

        <form class="navbar-form navbar-left width-half-full" method="get" action="<?= site_url('/sirkulasi') ?>">
            <div class="input-group width-full">
                <input type="text" name="katakunci" value="<?= esc($katakunci ?? '') ?>" class="form-control" placeholder="nomor arsip/kode user" />
                <span class="input-group-btn">
                    <button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i></button>
                </span>
            </div>
        </form>

        <div class="collapse navbar-collapse" id="module-submenu">
            <ul class="nav navbar-nav navbar-right">
                <?php if (hasModuleAccess('sirkulasi')): ?>
                    <li><a href="<?= site_url('/sirkulasi/new') ?>"><i class="glyphicon glyphicon-plus"></i> Peminjaman Baru</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Title -->
<div class="well well-sm">
    <div class="row">
        <div class="col-xs-9">Ditemukan data sebanyak : <em class="small">(<?= number_format($jml ?? 0) ?>)</em> peminjaman arsip</div>
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
                <th>Peminjam</th>
                <th>Keperluan</th>
                <th>Tgl. Pinjam</th>
                <th>Tgl. Harus Kembali</th>
                <th>Tgl. Pengembalian</th>
                <?php if ($admin ?? false): ?>
                    <th class="width-sm"></th>
                    <th class="width-sm"></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $a): ?>
                <tr>
                    <td><?= esc($a['noarsip']) ?></td>
                    <td><?= esc($a['username_peminjam']) ?></td>
                    <td><?= esc($a['keperluan']) ?></td>
                    <td><?= esc($a['tgl_pinjam']) ?></td>
                    <td><?= esc($a['tgl_haruskembali']) ?></td>
                    <td>
                        <?php if ($admin ?? false): ?>
                            <?php if ($a['tgl_pengembalian'] === null): ?>
                                <a href="#" id="<?= esc($a['id']) ?>" data-toggle="modal" data-target="#arsipkembali" class="btn btn-primary btn-xs kemdata">Kembalikan</a>
                            <?php else: ?>
                                <?= esc($a['tgl_pengembalian']) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= esc($a['tgl_pengembalian'] ?? '') ?>
                        <?php endif; ?>
                    </td>
                    <?php if ($admin ?? false): ?>
                        <td>
                            <a href="<?= site_url('/sirkulasi/edit/' . $a['id']) ?>"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span></a>
                        </td>
                        <td>
                            <a class="sdeldata" id="<?= esc($a['id']) ?>" href="#" data-toggle="modal" data-target="#deldata"><i class="glyphicon glyphicon-trash"></i></a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<!-- /.row -->

<?= $pages ?? '' ?>

<!-- Delete Modal -->
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
                <form id="fsdeldata" class="form-horizontal" role="form" method="post" action="<?= site_url('/sirkulasi/delete') ?>">
                    <h4 class="modal-title">Yakin ingin Hapus Data ini?</h4>
                    <input type="hidden" name="id" id="deliddata" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="sdeldatago">Hapus</button>
            </div>
        </div>
    </div>
</div>

<!-- Return Archive Modal -->
<div class="modal fade" id="arsipkembali">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Kembalikan Arsip</h4>
            </div>
            <div class="modal-body">
                <form id="fkemarsip" class="form-horizontal" role="form" method="post" action="<?= site_url('/sirkulasi/kembali') ?>">
                    <h4 class="modal-title">Yakin ingin kembalikan arsip ini?</h4>
                    <input type="hidden" name="id" id="kemid" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="kemarsipgo">Kembalikan</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>