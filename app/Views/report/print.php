<?php
/**
 * Print-friendly report view (print-to-PDF).
 *
 * Halaman mandiri (tanpa layout utama) yang otomatis membuka dialog cetak
 * browser saat dimuat. User memilih "Save as PDF" untuk mengekspor ke PDF.
 *
 * @var string  $title    Judul laporan
 * @var array   $headers  Daftar judul kolom
 * @var array   $rows     Daftar baris (array of array, urut sesuai $headers)
 * @var int     $total    Jumlah baris
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= esc($title) ?> - ARTERI</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            color: #222;
            margin: 24px;
            font-size: 12px;
        }
        .report-header { text-align: center; margin-bottom: 4px; }
        .report-header h1 { font-size: 18px; margin: 0 0 4px; }
        .report-meta {
            text-align: center;
            color: #666;
            font-size: 11px;
            margin-bottom: 16px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #999;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }
        thead th { background: #eee; font-weight: bold; }
        tbody tr:nth-child(even) td { background: #f7f7f7; }
        .text-empty { text-align: center; padding: 24px; color: #888; }
        .toolbar {
            text-align: center;
            margin-bottom: 16px;
        }
        .toolbar button {
            padding: 8px 18px;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid #2c3e50;
            background: #2c3e50;
            color: #fff;
            border-radius: 4px;
            margin: 0 4px;
        }
        .toolbar button.secondary {
            background: #fff;
            color: #2c3e50;
        }
        /* Saat mencetak, sembunyikan toolbar dan rapikan halaman */
        @media print {
            body { margin: 0; }
            .toolbar { display: none; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <button type="button" onclick="window.print()">&#128424; Cetak / Simpan PDF</button>
        <button type="button" class="secondary" onclick="window.close()">Tutup</button>
    </div>

    <div class="report-header">
        <h1><?= esc(strtoupper($title)) ?></h1>
    </div>
    <div class="report-meta">
        Dicetak: <?= date('d-m-Y H:i:s') ?> &middot; Total: <?= number_format($total ?? 0) ?> data
    </div>

    <?php if (! empty($rows)): ?>
        <table>
            <thead>
                <tr>
                    <?php foreach ($headers as $h): ?>
                        <th><?= esc($h) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <?php foreach ($row as $cell): ?>
                            <td><?= esc((string) $cell) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-empty">Tidak ada data yang sesuai dengan filter yang dipilih.</p>
    <?php endif; ?>

    <script>
        // Otomatis buka dialog cetak setelah halaman siap.
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
