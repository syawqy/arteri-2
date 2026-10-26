<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>
<?php
/**
 * Dashboard View - Statistik Arsip
 */
?>

<!-- Dashboard Header -->
<div class="row">
    <div class="col-lg-12">
        <h1 class="page-header">
            <i class="glyphicon glyphicon-dashboard"></i> Dashboard
            <small>Statistik Arsip</small>
        </h1>
    </div>
</div>

<!-- Summary Cards Row -->
<div class="row" id="stats-summary">
    <!-- Total Arsip Card - Skeleton -->
    <div class="col-lg-4 col-md-4 skeleton-wrapper" data-loaded="false">
        <div class="panel panel-primary skeleton-card">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <div class="skeleton skeleton-icon"></div>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="skeleton skeleton-number"></div>
                        <div class="skeleton skeleton-label"></div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Memuat...</span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <!-- Sedang Dipinjam Card - Skeleton -->
    <div class="col-lg-4 col-md-4 skeleton-wrapper" data-loaded="false">
        <div class="panel panel-green skeleton-card">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <div class="skeleton skeleton-icon"></div>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="skeleton skeleton-number"></div>
                        <div class="skeleton skeleton-label"></div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Memuat...</span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>

    <!-- Overdue Card - Skeleton -->
    <div class="col-lg-4 col-md-4 skeleton-wrapper" data-loaded="false">
        <div class="panel panel-red skeleton-card">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <div class="skeleton skeleton-icon"></div>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class="skeleton skeleton-number"></div>
                        <div class="skeleton skeleton-label"></div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <span class="pull-left">Memuat...</span>
                <div class="clearfix"></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Tables Row -->
<div class="row" id="stats-tables">
    <!-- Statistik Per Klasifikasi -->
    <div class="col-lg-6 col-md-6">
        <div class="panel panel-default" id="panel-klasifikasi">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-tag"></i> Statistik Per Klasifikasi
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody id="table-klasifikasi">
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Per Lokasi -->
    <div class="col-lg-6 col-md-6">
        <div class="panel panel-default" id="panel-lokasi">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-map-marker"></i> Statistik Per Lokasi
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Lokasi</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody id="table-lokasi">
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Second Row Charts -->
<div class="row">
    <!-- Statistik Per Media -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-default" id="panel-media">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-film"></i> Statistik Per Media
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Media</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody id="table-media">
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Per Pencipta -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-default" id="panel-pencipta">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-home"></i> Statistik Per Pencipta
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Pencipta</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody id="table-pencipta">
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Status -->
    <div class="col-lg-4 col-md-4">
        <div class="panel panel-default" id="panel-status">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-ok-circle"></i> Statistik Status
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody id="table-status">
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:60px;margin-left:auto;"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aktivitas Bulanan -->
<div class="row">
    <div class="col-lg-12">
        <div class="panel panel-default" id="panel-aktivitas">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-calendar"></i> Aktivitas Bulanan
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th class="text-right">Total Pinjaman</th>
                            </tr>
                        </thead>
                        <tbody id="table-aktivitas">
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:80px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:80px;margin-left:auto;"></div></td>
                            </tr>
                            <tr class="skeleton-row">
                                <td><div class="skeleton skeleton-cell"></div></td>
                                <td><div class="skeleton skeleton-cell" style="width:80px;margin-left:auto;"></div></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const summaryContainer = document.getElementById('stats-summary');
    const tablesContainer = document.getElementById('stats-tables');

    function showSkeleton(container, enabled) {
        container.style.opacity = enabled ? '0.5' : '1';
    }

    async function fetchDashboardData() {
        try {
            const [summaryRes, tablesRes] = await Promise.all([
                fetch(site_url + '/dashboard/api/stats'),
                fetch(site_url + '/dashboard/api/summary')
            ]);

            const summary = await summaryRes.json();
            const tables = await tablesRes.json();

            renderSummaryCards(summary);
            renderTables(tables);
        } catch (error) {
            console.error('Failed to load dashboard:', error);
        }
    }

    function renderSummaryCards(data) {
        const wrapper = summaryContainer;
        wrapper.innerHTML = `
            <div class="col-lg-4 col-md-4">
                <div class="panel panel-primary fade-in">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="glyphicon glyphicon-folder-open" style="font-size: 3em;"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge">${numberFormat(data.total_arsip || 0)}</div>
                                <div>Total Arsip</div>
                            </div>
                        </div>
                    </div>
                    <a href="${site_url}/search">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Detail</span>
                            <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="panel panel-green fade-in">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="glyphicon glyphicon-random" style="font-size: 3em;"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge">${numberFormat(data.sedang_dipinjam || 0)}</div>
                                <div>Sedang Dipinjam</div>
                            </div>
                        </div>
                    </div>
                    <a href="${site_url}/sirkulasi">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Detail</span>
                            <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="panel panel-red fade-in">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="glyphicon glyphicon-alert" style="font-size: 3em;"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge">${numberFormat(data.overdue || 0)}</div>
                                <div>Arsip Overdue</div>
                            </div>
                        </div>
                    </div>
                    <a href="${site_url}/sirkulasi?status=overdue">
                        <div class="panel-footer">
                            <span class="pull-left">Lihat Detail</span>
                            <span class="pull-right"><i class="glyphicon glyphicon-circle-arrow-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                </div>
            </div>
        `;
    }

    function renderTables(data) {
        const klasifikasiBody = document.getElementById('table-klasifikasi');
        const lokasiBody = document.getElementById('table-lokasi');
        const mediaBody = document.getElementById('table-media');
        const penciptaBody = document.getElementById('table-pencipta');
        const statusBody = document.getElementById('table-status');
        const aktivitasBody = document.getElementById('table-aktivitas');

        klasifikasiBody.innerHTML = renderTableRows(data.per_klasifikasi, [
            { key: 'kode', render: v => v || '-' },
            { key: 'nama', render: v => v || '-' },
            { key: 'total', render: v => numberFormat(v || 0), align: 'right' }
        ]);

        lokasiBody.innerHTML = renderTableRows(data.per_lokasi, [
            { key: 'nama_lokasi', render: v => v || '-' },
            { key: 'total', render: v => numberFormat(v || 0), align: 'right' }
        ]);

        mediaBody.innerHTML = renderTableRows(data.per_media, [
            { key: 'nama_media', render: v => v || '-' },
            { key: 'total', render: v => numberFormat(v || 0), align: 'right' }
        ]);

        penciptaBody.innerHTML = renderTableRows(data.per_pencipta, [
            { key: 'nama_pencipta', render: v => v || '-' },
            { key: 'total', render: v => numberFormat(v || 0), align: 'right' }
        ]);

        statusBody.innerHTML = renderTableRows(data.per_ket, [
            { key: 'ket', render: v => v || 'Belum diisi' },
            { key: 'total', render: v => numberFormat(v || 0), align: 'right' }
        ]);

        aktivitasBody.innerHTML = renderTableRows(data.aktivitas_bulanan, [
            { key: 'bulan', render: v => v || '-' },
            { key: 'total', render: v => numberFormat(v || 0), align: 'right' }
        ]);
    }

    function renderTableRows(data, columns) {
        if (!data || data.length === 0) {
            return `<tr><td colspan="${columns.length}" class="text-center text-muted">Tidak ada data</td></tr>`;
        }
        return data.map(row => {
            const cells = columns.map(col => {
                const value = col.render(row[col.key]);
                const align = col.align ? `text-${col.align}` : '';
                return `<td class="${align}">${value}</td>`;
            }).join('');
            return `<tr class="fade-in">${cells}</tr>`;
        }).join('');
    }

    function numberFormat(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    fetchDashboardData();
});
</script>
<?= $this->endSection() ?>