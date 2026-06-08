<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<?php
/**
 * Halaman Sampah / Recycle Bin (admin only).
 *
 * @var array $groups        Daftar grup per entitas: [type => [label, type, items[], count]]
 * @var int   $recoveryDays  Masa pemulihan (hari)
 */
?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-trash"></i> Sampah
            <small>Data terhapus</small>
        </h1>
        <div class="alert alert-info">
            <i class="glyphicon glyphicon-info-sign"></i>
            Data di sampah dapat dipulihkan dalam <strong><?= esc($recoveryDays) ?> hari</strong>.
            Setelah itu akan dihapus permanen secara otomatis.
        </div>
    </div>
</div>

<!-- Tabs per entitas -->
<ul class="nav nav-tabs" role="tablist">
    <?php $first = true; foreach ($groups as $g): ?>
        <li role="presentation" class="<?= $first ? 'active' : '' ?>">
            <a href="#tab-<?= esc($g['type'], 'attr') ?>" aria-controls="tab-<?= esc($g['type'], 'attr') ?>" role="tab" data-toggle="tab">
                <?= esc($g['label']) ?>
                <span class="badge"><?= esc($g['count']) ?></span>
            </a>
        </li>
    <?php $first = false; endforeach; ?>
</ul>

<div class="tab-content" style="margin-top: 15px;">
    <?php $first = true; foreach ($groups as $g): ?>
        <div role="tabpanel" class="tab-pane fade <?= $first ? 'in active' : '' ?>" id="tab-<?= esc($g['type'], 'attr') ?>">
            <div class="panel panel-default">
                <div class="panel-body">
                    <?php if (! empty($g['items'])): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th class="width-sm">No</th>
                                        <th><?= esc($g['label']) ?></th>
                                        <th>Dihapus pada</th>
                                        <th class="width-sm">Sisa hari</th>
                                        <th class="width-sm">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($g['items'] as $item): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= esc($item['display']) ?></td>
                                            <td><?= esc($item['deleted_at'] ?? '-') ?></td>
                                            <td>
                                                <?php $dl = $item['days_left']; ?>
                                                <span class="label <?= ($dl !== null && $dl <= 3) ? 'label-danger' : 'label-default' ?>">
                                                    <?= $dl === null ? '-' : esc($dl) . ' hari' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-success trash-restore"
                                                    data-type="<?= esc($g['type'], 'attr') ?>" data-id="<?= esc($item['id'], 'attr') ?>"
                                                    title="Pulihkan">
                                                    <i class="glyphicon glyphicon-refresh"></i> Pulihkan
                                                </button>
                                                <button type="button" class="btn btn-xs btn-danger trash-purge"
                                                    data-type="<?= esc($g['type'], 'attr') ?>" data-id="<?= esc($item['id'], 'attr') ?>"
                                                    title="Hapus Permanen">
                                                    <i class="glyphicon glyphicon-trash"></i> Hapus Permanen
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted" style="margin: 0;">Tidak ada data di sampah.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php $first = false; endforeach; ?>
</div>

<!-- Restore confirm modal -->
<div class="modal fade" id="restoreModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Pulihkan Data</h4>
      </div>
      <div class="modal-body">
        <form id="formRestore" method="post" action="<?= site_url('trash/restore') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" id="restoreType" value="">
          <input type="hidden" name="id" id="restoreId" value="">
          <p>Yakin ingin memulihkan data ini dari sampah?</p>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-success" id="restoreGo">Pulihkan</button>
      </div>
    </div>
  </div>
</div>

<!-- Purge confirm modal -->
<div class="modal fade" id="purgeModal">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Hapus Permanen</h4>
      </div>
      <div class="modal-body">
        <form id="formPurge" method="post" action="<?= site_url('trash/purge') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="type" id="purgeType" value="">
          <input type="hidden" name="id" id="purgeId" value="">
          <p class="text-danger"><strong>Peringatan:</strong> Data akan dihapus permanen dan tidak dapat dipulihkan. Lanjutkan?</p>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-danger" id="purgeGo">Hapus Permanen</button>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
