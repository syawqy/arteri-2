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
  		<div class="panel-body"><a href="<?= site_url('/dl') ?>" class="btn btn-success" id="export">Export</a></div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading">Import data <a href="<?= base_url('/template import arteri.xlsx') ?>" class="btn btn-success btn-sm" id="export">File template</a></div>
		<div class="panel-body">
			<form id="import_data" action="<?= site_url('/import') ?>" enctype="multipart/form-data" class="form-horizontal" method="post" role="form">
				<?= csrf_field() ?>
				<label class="control-label" for="up_file">Upload</label>
				<input type="file" name="up_file" id="up_file" required/>
				<input type="submit" value="Upload" class="submit" />
			</form>
		</div>
	</div>
</div>
<?= $this->endSection() ?>
