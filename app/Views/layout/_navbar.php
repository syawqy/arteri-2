<!-- Shared Navigation Bar -->
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
            <a style="padding-top: 13px;" class="navbar-brand" href="<?= site_url('/') ?>"><img src="<?= base_url('/images/logo-horizontal.png') ?>" alt="ARTERI" height="35"></a>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="arteri-main-menu">
            <ul class="nav navbar-nav">
                <li><a href="<?= site_url('/dashboard') ?>"><i class="glyphicon glyphicon-dashboard"></i> Dashboard</a></li>
                <li><a href="<?= site_url('/report') ?>"><i class="glyphicon glyphicon-print"></i> Laporan</a></li>
                <li><a href="<?= site_url('/chat') ?>"><i class="glyphicon glyphicon-comment"></i> AI Assistant</a></li>
                <?php if (hasModuleAccess('entridata')): ?>
                    <li><a href="<?= site_url('/arsip/new') ?>"><i class="glyphicon glyphicon-plus"></i> Entri Data</a></li>
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
                                <li><a href="<?= site_url('master/klas') ?>"><i class="glyphicon glyphicon-tag"></i> Klasifikasi</a></li>
                            <?php endif; ?>
                            <?php if (hasModuleAccess('pencipta')): ?>
                                <li><a href="<?= site_url('master/penc') ?>"><i class="glyphicon glyphicon-home"></i> Pencipta arsip</a></li>
                            <?php endif; ?>
                            <?php if (hasModuleAccess('pengolah')): ?>
                                <li><a href="<?= site_url('master/pengolah') ?>"><i class="glyphicon glyphicon-home"></i> Unit Pengolah</a></li>
                            <?php endif; ?>
                            <?php if (hasModuleAccess('lokasi')): ?>
                                <li><a href="<?= site_url('master/lokasi') ?>"><i class="glyphicon glyphicon-map-marker"></i> Lokasi</a></li>
                            <?php endif; ?>
                            <?php if (hasModuleAccess('media')): ?>
                                <li><a href="<?= site_url('master/media') ?>"><i class="glyphicon glyphicon-film"></i> Media</a></li>
                            <?php endif; ?>
                            <?php if (hasModuleAccess('user')): ?>
                                <li><a href="<?= site_url('user') ?>"><i class="glyphicon glyphicon-user"></i> User</a></li>
                            <?php endif; ?>
                            <?php if (hasModuleAccess('import')): ?>
                                <li><a href="<?= site_url('import') ?>"><i class="glyphicon glyphicon-tasks"></i> Import data</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <?php if (session('username')): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                            <span class="glyphicon glyphicon-user"></span> <?= esc(session('username')) ?> <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if (isAdmin()): ?>
                                <li><a href="<?= site_url('audit') ?>"><span class="glyphicon glyphicon-list-alt"></span> Audit Log</a></li>
                                <li><a href="<?= site_url('trash') ?>"><span class="glyphicon glyphicon-trash"></span> Sampah</a></li>
                                <li role="separator" class="divider"></li>
                            <?php endif; ?>
                            <li><a href="<?= site_url('logout') ?>"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="<?= site_url('login') ?>"><span class="glyphicon glyphicon-log-in"></span> Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
    <!-- /.container -->
</nav>
