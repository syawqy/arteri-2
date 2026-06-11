<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Arsip Overdue</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-top: none;
        }
        .arsip-info {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info-row {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
            color: #495057;
        }
        .value {
            color: #212529;
        }
        .overdue-days {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.1em;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>⚠️ Arsip Overdue</h2>
    </div>
    <div class="content">
        <p>Kepada <?= esc($peminjam_nama ?? $peminjam_username) ?>,</p>

        <p>Arsip yang Anda pinjam telah <strong class="overdue-days">melewati batas waktu pengembalian</strong>.</p>

        <div class="arsip-info">
            <div class="info-row">
                <span class="label">Nomor Arsip:</span>
                <span class="value"><?= esc($noarsip) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Uraian:</span>
                <span class="value"><?= esc($uraian) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Tanggal Pinjam:</span>
                <span class="value"><?= esc($tgl_pinjam) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Harus Dikembalikan:</span>
                <span class="value"><?= esc($tgl_haruskembali) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Terlambat:</span>
                <span class="value overdue-days"><?= esc($hari_terlambat) ?> hari</span>
            </div>
        </div>

        <p>Mohon segera mengembalikan arsip ini ke unit kearsipan atau hubungi admin untuk perpanjangan.</p>

        <p>Terima kasih atas perhatian Anda.</p>
    </div>
    <div class="footer">
        <p>Email ini dikirim otomatis oleh sistem Arteri. Jangan balas email ini.</p>
        <p>&copy; <?= date('Y') ?> Sistem Manajemen Arsip Arteri</p>
    </div>
</body>
</html>
