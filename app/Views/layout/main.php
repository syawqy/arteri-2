<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>ARTERI<?php if (isset($title)): ?> - <?= esc($title) ?><?php endif; ?></title>

    <!-- Bootstrap Core CSS -->
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/flatly.bootstrap.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/heroic-features.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/jquery-ui.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/jquery-ui.structure.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/jquery-ui.theme.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/chosen.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/jquery.auto-complete.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/custom.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/css/loading.css') ?>" />
    <meta name="<?= csrf_token() ?>" data-name="<?= csrf_token() ?>" data-value="<?= csrf_hash() ?>" content="<?= csrf_hash() ?>">
    <script>
        var base_url = '<?= base_url() ?>';
        var site_url = '<?= site_url() ?>';
    </script>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <link href="<?= base_url('/logo.png') ?>" rel="icon" />

</head>

<body>

    <!-- Navigation -->
    <?= $this->include('layout/_navbar') ?>

    <!-- Page Content -->
    <div class="container">

        <?= $this->renderSection('content') ?>

    </div>
    <!-- /.container -->

    <!-- Footer -->
    <footer>
      <p>ARTERI Arsip Elektronik Terintegrasi</p>
    </footer>
    <!-- /.Footer -->

    <!-- jQuery -->
    <script src="<?= base_url('/js/jquery-2.2.2.min.js') ?>"></script>
    <script src="<?= base_url('/js/bootstrap.min.js') ?>"></script>
    <script src="<?= base_url('/js/jquery-ui.min.js') ?>"></script>
    <script src="<?= base_url('/js/jquery.form.min.js') ?>"></script>
    <script src="<?= base_url('/js/chosen.jquery.min.js') ?>"></script>
    <script src="<?= base_url('/js/jquery.auto-complete.min.js') ?>"></script>
    <script src="<?= base_url('/js/custom.js') ?>"></script>
    <script src="<?= base_url('/js/ux-enhancements.js') ?>"></script>

</body>
</html>