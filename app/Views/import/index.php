<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<h2>Import Data</h2>
<hr>
<?php if ($zz = session()->getFlashdata('zz')): ?>
    <div class="alert alert-danger" role="alert"><?= esc($zz) ?></div>
<?php endif; ?>
<?php if ($message = session()->getFlashdata('message')): ?>
    <div class="alert alert-success" role="alert"><?= esc($message) ?></div>
<?php endif; ?>
<?php if ($error = session()->getFlashdata('error')): ?>
    <div class="alert alert-danger" role="alert"><?= esc($error) ?></div>
<?php endif; ?>
<div class="row">
	<div class="panel panel-default">
		<div class="panel-heading">Export data</div>
  		<div class="panel-body"><a href="<?= site_url('/dl') ?>" class="btn btn-success js-export" id="export">Export</a></div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">Import data <a href="<?= base_url('/template import arteri.xlsx') ?>" class="btn btn-success btn-sm">File template</a></div>
		<div class="panel-body">
			<form id="import_data" action="<?= site_url('/import') ?>" enctype="multipart/form-data" class="form-horizontal" method="post" role="form">
				<?= csrf_field() ?>
				<label class="control-label" for="up_file">Upload</label>
				<input type="file" name="up_file" id="up_file" data-dropzone accept=".xls,.xlsx" required/>
				<p class="help-block">Format: Excel (.xls / .xlsx)</p>
				<div id="import_progress"></div>
				<div id="import_result"></div>
				<button type="submit" class="btn btn-primary submit">Upload</button>
			</form>
		</div>
	</div>
</div>

<script>
$(function () {
	var $form = $('#import_data');
	if (!$form.length || typeof window.ArteriProgress === 'undefined') return;

	$form.on('submit', function (e) {
		var input = document.getElementById('up_file');
		if (!input.files || !input.files.length) return; // biarkan validasi 'required' bekerja
		e.preventDefault();

		var $btn = $form.find('button[type="submit"]');
		$btn.prop('disabled', true).addClass('btn-loading');
		$('#import_result').empty();
		$('#import_progress').empty();
		var progress = ArteriProgress.create('#import_progress', 'Mengunggah & memproses...');

		var fd = new FormData($form[0]);
		var xhr = new XMLHttpRequest();
		xhr.open('POST', $form.attr('action'), true);
		xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

		xhr.upload.onprogress = function (evt) {
			if (evt.lengthComputable) {
				var pct = (evt.loaded / evt.total) * 100;
				progress.set(pct);
				if (pct >= 100) { progress.label('Memproses data di server...').indeterminate(); }
			}
		};

		xhr.onload = function () {
			$btn.prop('disabled', false).removeClass('btn-loading');
			var resp;
			try { resp = JSON.parse(xhr.responseText); } catch (err) { resp = null; }

			if (xhr.status >= 200 && xhr.status < 300 && resp && resp.status === 'success') {
				progress.label('Selesai').done();
				$('#import_result').html('<div class="alert alert-success">' + $('<span>').text(resp.message).html() + '</div>');
				showToast(resp.message || 'Import berhasil.');
				input.value = '';
				$form.find('.arteri-dropzone .arteri-dropzone-clear').trigger('click');
			} else {
				progress.remove();
				var msg = (resp && resp.message) ? resp.message : 'Gagal mengimport data.';
				$('#import_result').html('<div class="alert alert-danger">' + $('<span>').text(msg).html() + '</div>');
				showToast(msg, 'error');
			}
		};

		xhr.onerror = function () {
			$btn.prop('disabled', false).removeClass('btn-loading');
			progress.remove();
			showToast('Terjadi kesalahan jaringan saat upload.', 'error');
		};

		xhr.send(fd);
	});
});
</script>
<?= $this->endSection() ?>
