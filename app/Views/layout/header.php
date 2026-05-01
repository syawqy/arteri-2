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
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/flatly.bootstrap.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/heroic-features.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/jquery-ui.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/jquery-ui.structure.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/jquery-ui.theme.min.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/chosen.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/jquery.auto-complete.css') ?>" />
    <link type="text/css" rel="stylesheet" href="<?= base_url('/public/css/custom.css') ?>" />
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
    <link href="<?= base_url('/public/logo.png') ?>" rel="icon" />

</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">

            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#arteri-main-menu">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a style="padding-top: 13px;" class="navbar-brand" href="<?= site_url('/home') ?>"><img src="<?= base_url('/public/images/logo-horizontal.png') ?>" alt="ARTERI" height="35"></a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="arteri-main-menu">
                <ul class="nav navbar-nav">
                    <?php if (hasModuleAccess('entridata')): ?>
                        <li><a href="<?= site_url('/admin/entr') ?>"><i class="glyphicon glyphicon-plus"></i> Entri Data Baru</a></li>
                    <?php endif; ?>
                    <?php if (hasModuleAccess('sirkulasi')): ?>
                        <li><a href="<?= site_url('/sirkulasi') ?>"><i class="glyphicon glyphicon-refresh"></i> Sirkulasi</a></li>
                    <?php endif; ?>
                    <?php
                    $hasMasterAccess = hasModuleAccess('klasifikasi')
                        || hasModuleAccess('pencipta')
                        || hasModuleAccess('pengolah')
                        || hasModuleAccess('lokasi')
                        || hasModuleAccess('media')
                        || hasModuleAccess('user')
                        || hasModuleAccess('import');
                    ?>
                    <?php if ($hasMasterAccess): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                <i class="glyphicon glyphicon-th-large"></i> Data Master <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (hasModuleAccess('klasifikasi')): ?>
                                    <li><a href="<?= site_url('admin/klas') ?>"><i class="glyphicon glyphicon-tag"></i> Klasifikasi</a></li>
                                <?php endif; ?>
                                <?php if (hasModuleAccess('pencipta')): ?>
                                    <li><a href="<?= site_url('admin/penc') ?>"><i class="glyphicon glyphicon-home"></i> Pencipta arsip</a></li>
                                <?php endif; ?>
                                <?php if (hasModuleAccess('pengolah')): ?>
                                    <li><a href="<?= site_url('admin/pengolah') ?>"><i class="glyphicon glyphicon-home"></i> Unit Pengolah</a></li>
                                <?php endif; ?>
                                <?php if (hasModuleAccess('lokasi')): ?>
                                    <li><a href="<?= site_url('admin/lokasi') ?>"><i class="glyphicon glyphicon-map-marker"></i> Lokasi</a></li>
                                <?php endif; ?>
                                <?php if (hasModuleAccess('media')): ?>
                                    <li><a href="<?= site_url('admin/media') ?>"><i class="glyphicon glyphicon-film"></i> Media</a></li>
                                <?php endif; ?>
                                <?php if (hasModuleAccess('user')): ?>
                                    <li><a href="<?= site_url('admin/vuser') ?>"><i class="glyphicon glyphicon-user"></i> User</a></li>
                                <?php endif; ?>
                                <?php if (hasModuleAccess('import')): ?>
                                    <li><a href="<?= site_url('admin/import') ?>"><i class="glyphicon glyphicon-tasks"></i> Import data</a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php if (session('username')): ?>
                        <li><a href="#"><span class="glyphicon glyphicon-user"></span> <?= esc(session('username')) ?></a></li>
                        <li><a href="<?= site_url('home/logout') ?>"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= site_url('home/login') ?>"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

    <!-- Page Content -->
    <div class="container">
