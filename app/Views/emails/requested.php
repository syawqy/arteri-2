<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Request Arsip</title>
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
            background: #0d6efd;
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
            border-left: 4px solid #0d6efd;
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
        .requester {
            color: #0d6efd;
            font-weight: bold;
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
        <h2>📬 Request Arsip</h2>
    </div>
    <div class="content">
        <p>Kepada <?= esc($peminjam_nama ?? $peminjam_username) ?>,</p>

        <p>Arsip yang sedang Anda pinjam <strong>diminta oleh pengguna lain</strong>.</p>

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
                <span class="label">Harus Dikembalikan:</span>
                <span class="value"><?= esc($tgl_haruskembali) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Direquest oleh:</span>
                <span class="value requester"><?= esc($requester_nama ?? $requester_username) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Tanggal Request:</span>
                <span class="value"><?= esc($tgl_request) ?></span>
            </div>
        </div>

        <p>Jika Anda sudah selesai menggunakan arsip ini, mohon segera mengembalikannya ke unit kearsipan agar dapat digunakan oleh pengguna yang membutuhkan.</p>

        <p>Terima kasih atas kerjasamanya.</p>
    </div>
    <div class="footer">
        <p>Email ini dikirim otomatis oleh sistem Arteri. Jangan balas email ini.</p>
        <p>&copy; <?= date('Y') ?> Sistem Manajemen Arsip Arteri</p>
    </div>
</body>
</html>
