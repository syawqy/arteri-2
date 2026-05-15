<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h1 class="sub-header">Audit Log</h1>

<!-- Filter -->
<div class="panel panel-primary">
    <div class="panel-heading">Filter</div>
    <div class="panel-body bg-light">
        <form method="get" action="<?= site_url('audit') ?>" class="form-inline">
            <div class="form-group">
                <label for="tgl_dari">Dari</label>
                <input type="date" name="tgl_dari" class="form-control" value="<?= esc($filters['tgl_dari']) ?>">
            </div>
            <div class="form-group">
                <label for="tgl_sampai">Sampai</label>
                <input type="date" name="tgl_sampai" class="form-control" value="<?= esc($filters['tgl_sampai']) ?>">
            </div>
            <div class="form-group">
                <label for="aksi">Aksi</label>
                <select name="aksi" class="form-control">
                    <option value="">-- Semua --</option>
                    <?php foreach ($aksiList as $item): ?>
                        <option value="<?= esc($item['aksi']) ?>" <?= $filters['aksi'] === $item['aksi'] ? 'selected' : '' ?>><?= esc($item['aksi']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tabel">Tabel</label>
                <select name="tabel" class="form-control">
                    <option value="">-- Semua --</option>
                    <?php foreach ($tabelList as $item): ?>
                        <option value="<?= esc($item['tabel']) ?>" <?= $filters['tabel'] === $item['tabel'] ? 'selected' : '' ?>><?= esc($item['tabel']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="keyword">Cari</label>
                <input type="text" name="keyword" class="form-control" placeholder="username/kode/detail" value="<?= esc($filters['keyword']) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="<?= site_url('audit') ?>" class="btn btn-default">Reset</a>
        </form>
    </div>
</div>

<!-- Table -->
<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Tanggal</th>
                <th>Username</th>
                <th>Aksi</th>
                <th>Tabel</th>
                <th>Record ID</th>
                <th>IP Address</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="8" class="text-center">Tidak ada data.</td></tr>
            <?php else: ?>
                <?php $no = ($page - 1) * 50; foreach ($logs as $l): $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><?= esc($l['tgl_transaksi']) ?></td>
                    <td><?= esc($l['username_transaksi']) ?></td>
                    <?php
                    $aksiClass = '';
                    if (strpos($l['aksi'] ?? '', 'FAILED') !== false || strpos($l['aksi'] ?? '', 'DELETE') !== false) $aksiClass = 'text-danger';
                    if (strpos($l['aksi'] ?? '', 'LOGIN') !== false) $aksiClass = 'text-info';
                    ?>
                    <td><span class="<?= $aksiClass ?>" style="font-size:11px;"><?= esc($l['aksi'] ?? '-') ?></span></td>
                    <td><?= esc($l['tabel'] ?? '-') ?></td>
                    <td><?= $l['record_id'] ? esc((string) $l['record_id']) : '-' ?></td>
                    <td><?= esc($l['ip_address'] ?? '-') ?></td>
                    <td>
                        <?php if (!empty($l['detail'])): ?>
                            <a href="#" class="audit-detail-toggle" data-id="<?= $l['id'] ?>" style="font-size:11px;">Lihat</a>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="text-center">
    <ul class="pagination">
        <?php
        $queryParams = $_GET;
        for ($i = 1; $i <= $totalPages; $i++):
            $queryParams['page'] = $i;
            $qs = http_build_query($queryParams);
            $active = $i == $page ? 'active' : '';
        ?>
            <li class="<?= $active ?>"><a href="<?= site_url('audit?' . $qs) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Detail Modal -->
<div class="modal fade" id="auditDetailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detail Audit Log</h4>
            </div>
            <div class="modal-body">
                <pre id="auditDetailContent" style="max-height:400px;overflow:auto;background:#f5f5f5;padding:10px;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('.audit-detail-toggle').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.get('<?= site_url('audit/detail/') ?>' + id, function(res) {
            try { var data = typeof res === 'string' ? JSON.parse(res) : res; }
            catch(e) { data = res; }
            if (data && data.status === 'success' && data.data && data.data.detail) {
                var detail = typeof data.data.detail === 'string' ? JSON.parse(data.data.detail) : data.data.detail;
                $('#auditDetailContent').text(JSON.stringify(detail, null, 2));
            } else {
                $('#auditDetailContent').text('Tidak ada detail.');
            }
            $('#auditDetailModal').modal('show');
        });
    });
});
</script>
<?= $this->endSection() ?>