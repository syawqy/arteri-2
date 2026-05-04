<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Login</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link type="text/css" rel="stylesheet" href="<?= base_url('/css/bootstrap.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/login.css') ?>" />
    <link href="<?= base_url('/logo.png') ?>" rel="icon" />
</head>
<body>

<div class="container" id=logindiv>
	<div class="row">
    	<div class="container" id="formContainer">

          <form class="form-signin" id="login" role="form" method="post" action="<?= site_url('/login') ?>">
		  <p align="center"><img src="<?= base_url('/logo-full.png') ?>" class="img-responsive"></p>
            <h5 class="form-signin-heading"><p align="center">Masukkan username dan password anda</p></h5>
			<?php
			if (session()->getFlashdata('erorlogin')) {
				echo '<div style="color:red; text-align:center;">' . esc(session()->getFlashdata('erorlogin')) . '</div>';
			}
			?>
            <input type="hidden" name="previous" value="<?= isset($previous) ? esc($previous) : '' ?>">
            <input type="text" class="form-control" name="username" id="loginEmail" placeholder="username" required autofocus>
            <input type="password" class="form-control" name="password" id="loginPass" placeholder="sandi" required>
            <button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
            <br><p align="center">Arteri Open Source</p>
          </form>

        </div>
	</div>
</div>
<script src="<?= base_url('/js/jquery-2.2.2.min.js')?>"></script>
<script src="<?= base_url('/js/bootstrap.min.js') ?>"></script>
</body>
</html>
