<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-dashboard"></i> Dashboard
            <small>Statistik Arsip Digital</small>
        </h1>
    </div>
</div>

<div class="row" id="stats-summary">
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-folder-open" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" id="total-arsip">-</div>
                        <div>Total Arsip</div>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('search') ?>">
                <div class="panel-footer">
                    <span class="pull-left">Lihat Detail</span>
                    <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-random" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" id="sedang-dipinjam">-</div>
                        <div>Sedang Dipinjam</div>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('sirkulasi') ?>">
                <div class="panel-footer">
                    <span class="pull-left">Lihat Detail</span>
                    <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="glyphicon glyphicon-alert" style="font-size: 3em;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="huge" id="overdue">-</div>
                        <div>Arsip Overdue</div>
                    </div>
                </div>
            </div>
            <a href="<?= site_url('sirkulasi?status=overdue') ?>">
                <div class="panel-footer">
                    <span class="pull-left">Lihat Detail</span>
                    <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-barcode"></i> Statistik Per Klasifikasi
                <div class="pull-right">
                    <a href="<?= site_url('dashboard/api/by-klasifikasi') ?>" class="btn btn-xs btn-default">
                        <i class="glyphicon glyphicon-download"></i> Export Data
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="chart-klasifikasi" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-map-marker"></i> Statistik Per Lokasi
                <div class="pull-right">
                    <a href="<?= site_url('dashboard/api/by-lokasi') ?>" class="btn btn-xs btn-default">
                        <i class="glyphicon glyphicon-download"></i> Export Data
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="chart-lokasi" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-ok-circle"></i> Statistik Status Arsip
                <div class="pull-right">
                    <a href="<?= site_url('dashboard/api/by-ket') ?>" class="btn btn-xs btn-default">
                        <i class="glyphicon glyphicon-download"></i> Export Data
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="chart-status" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-film"></i> Statistik Per Media
                <div class="pull-right">
                    <a href="<?= site_url('dashboard/api/by-media') ?>" class="btn btn-xs btn-default">
                        <i class="glyphicon glyphicon-download"></i> Export Data
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="chart-media" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-home"></i> Statistik Per Pencipta
                <div class="pull-right">
                    <a href="<?= site_url('dashboard/api/by-pencipta') ?>" class="btn btn-xs btn-default">
                        <i class="glyphicon glyphicon-download"></i> Export Data
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="chart-pencipta" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-calendar"></i> Aktivitas Bulanan (Pinjaman)
                <div class="pull-right">
                    <a href="<?= site_url('dashboard/api/by-bulan?months=6') ?>" class="btn btn-xs btn-default">
                        <i class="glyphicon glyphicon-download"></i> Export Data
                    </a>
                </div>
            </div>
            <div class="panel-body">
                <canvas id="chart-aktivitas" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    async function fetchDashboardData() {
        try {
            const response = await fetch('<?= site_url('dashboard/api/stats') ?>');
            const data = await response.json();
            renderSummaryCards(data);
            renderCharts(data);
        } catch (error) {
            console.error('Failed to load dashboard:', error);
        }
    }

    function renderSummaryCards(data) {
        document.getElementById('total-arsip').textContent = data.total_arsip || 0;
        document.getElementById('sedang-dipinjam').textContent = data.sedang_dipinjam || 0;
        document.getElementById('overdue').textContent = data.overdue || 0;
    }

    function renderCharts(data) {
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        };

        new Char
