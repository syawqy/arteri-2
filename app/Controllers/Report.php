<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\SirkulasiModel;
use App\Models\MasterKodeModel;
use App\Traits\JsonResponseTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * ReportController - Laporan Arsip dan Sirkulasi
 */
class Report extends BaseController
{
    use JsonResponseTrait;

    private int $perPage = 50;

    /**
     * Halaman utama laporan
     */
    public function index()
    {
        $this->logPageView('report');

        $data['title'] = 'Laporan';
        helper('form');

        // Load master data untuk filter
        $data['kode'] = (new MasterKodeModel())->orderBy('kode', 'ASC')->findAll();

        return view('report/index', $data);
    }

    /**
     * Generate laporan arsip dalam format HTML
     * GET /report/arsip
     */
    public function arsip()
    {
        $this->logAction('VIEW_REPORT', 'report_arsip', null);

        helper('form');
        $keywords = $this->request->getGet('katakunci') ?? '';
        $kode = $this->request->getGet('kode') ?? '';
        $tanggal_from = $this->request->getGet('tanggal_from') ?? '';
        $tanggal_to = $this->request->getGet('tanggal_to') ?? '';
        $ket = $this->request->getGet('ket') ?? '';

        $arsipModel = new ArsipModel();
        $filters = [
            'noarsip' => '',
            'tanggal' => '',
            'uraian' => $keywords,
            'ket' => $ket,
            'kode' => $kode,
            'retensi' => '',
            'penc' => '',
            'peng' => '',
            'lok' => '',
            'med' => '',
            'nobox' => '',
        ];

        // Filter tanggal
        if ($tanggal_from) {
            $filters['tanggal'] = $tanggal_from;
        }
        if ($tanggal_to) {
            $filters['tanggal'] = $tanggal_from . ' - ' . $tanggal_to;
        }

        $data['results'] = $arsipModel->search($keywords, $filters, 0, 0);
        $data['total'] = count($data['results']);
        $data['filters'] = [
            'katakunci' => $keywords,
            'kode' => $kode,
            'tanggal_from' => $tanggal_from,
            'tanggal_to' => $tanggal_to,
            'ket' => $ket,
        ];

        $data['title'] = 'Laporan Arsip';
        $data['kode'] = (new MasterKodeModel())->orderBy('kode', 'ASC')->findAll();

        return view('report/arsip', $data);
    }

    /**
     * Generate laporan sirkulasi dalam format HTML
     * GET /report/sirkulasi
     */
    public function sirkulasi()
    {
        $this->logAction('VIEW_REPORT', 'report_sirkulasi', null);

        helper('form');
        $username = $this->request->getGet('username') ?? '';
        $status = $this->request->getGet('status') ?? '';
        $tanggal_from = $this->request->getGet('tanggal_from') ?? '';
        $tanggal_to = $this->request->getGet('tanggal_to') ?? '';

        $sirkulasiModel = new SirkulasiModel();
        $filters = [
            'username' => $username,
            'status' => $status,
            'tanggal_from' => $tanggal_from,
            'tanggal_to' => $tanggal_to,
        ];

        $data['results'] = $sirkulasiModel->search($username, $filters, 0, 0);
        $data['total'] = count($data['results']);
        $data['filters'] = $filters;

        $data['title'] = 'Laporan Sirkulasi';

        return view('report/sirkulasi', $data);
    }

    /**
     * Export laporan arsip ke Excel
     * GET /report/arsip/export-excel
     */
    public function exportArsipExcel()
    {
        $this->logAction('EXPORT', 'report_arsip_excel', null);

        $keywords = $this->request->getGet('katakunci') ?? '';
        $kode = $this->request->getGet('kode') ?? '';
        $tanggal_from = $this->request->getGet('tanggal_from') ?? '';
        $tanggal_to = $this->request->getGet('tanggal_to') ?? '';
        $ket = $this->request->getGet('ket') ?? '';

        $arsipModel = new ArsipModel();
        $filters = [
            'noarsip' => '',
            'tanggal' => '',
            'uraian' => $keywords,
            'ket' => $ket,
            'kode' => $kode,
            'retensi' => '',
            'penc' => '',
            'peng' => '',
            'lok' => '',
            'med' => '',
            'nobox' => '',
        ];

        $data = $arsipModel->search($keywords, $filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Arsip');

        // Title
        $sheet->setCellValue('A1', 'LAPORAN ARSIP');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tanggal export
        $sheet->setCellValue('A2', 'Dicetak: ' . date('d-m-Y H:i:s'));
        $sheet->mergeCells('A2:M2');

        // Headers
        $headers = ['No.', 'No.Arsip', 'Tanggal', 'Kode Klasifikasi', 'Uraian', 'Pencipta',
                     'Pengolah', 'Media', 'Lokasi', 'Ket', 'Jumlah', 'No.Box', 'Retensi'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        $row = 5;
        $no = 1;
        foreach ($data as $d) {
            $sheet->setCellValueExplicit('A' . $row, $no, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B' . $row, (string) ($d['noarsip'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, (string) ($d['tanggal'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, (string) ($d['nama_kode'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $row, (string) ($d['uraian'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $row, (string) ($d['nama_pencipta'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $row, (string) ($d['nama_pengolah'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('H' . $row, (string) ($d['nama_media'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('I' . $row, (string) ($d['nama_lokasi'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('J' . $row, (string) ($d['ket'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('K' . $row, (string) ($d['jumlah'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('L' . $row, (string) ($d['nobox'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('M' . $row, (string) ($d['b'] ?? ''), DataType::TYPE_STRING);
            $row++;
            $no++;
        }

        $filename = 'Laporan Arsip ' . date('Y-m-d His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $cachePath = WRITEPATH . 'cache';
        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $tempFile = tempnam($cachePath, 'arteri-report-');
        $writer->save($tempFile);
        $body = file_get_contents($tempFile);
        unlink($tempFile);

        return $this->response
            ->download($filename, $body, true)
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Transfer-Encoding', 'binary')
            ->setHeader('Content-Length', (string) strlen($body))
            ->setHeader('Cache-Control', 'max-age=0');
    }

    /**
     * Export laporan sirkulasi ke Excel
     * GET /report/sirkulasi/export-excel
     */
    public function exportSirkulasiExcel()
    {
        $this->logAction('EXPORT', 'report_sirkulasi_excel', null);

        $username = $this->request->getGet('username') ?? '';
        $status = $this->request->getGet('status') ?? '';
        $tanggal_from = $this->request->getGet('tanggal_from') ?? '';
        $tanggal_to = $this->request->getGet('tanggal_to') ?? '';

        $sirkulasiModel = new SirkulasiModel();
        $filters = [
            'username' => $username,
            'status' => $status,
            'tanggal_from' => $tanggal_from,
            'tanggal_to' => $tanggal_to,
        ];

        $data = $sirkulasiModel->search($username, $filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Sirkulasi');

        // Title
        $sheet->setCellValue('A1', 'LAPORAN SIRKULASI');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Tanggal export
        $sheet->setCellValue('A2', 'Dicetak: ' . date('d-m-Y H:i:s'));
        $sheet->mergeCells('A2:I2');

        // Headers
        $headers = ['No.', 'No.Arsip', 'Uraian', 'Peminjam', 'Tgl Pinjam', 'Tgl Harus Kembali',
                     'Tgl Kembali', 'Status', 'Keterangan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        $row = 5;
        $no = 1;
        foreach ($data as $d) {
            $status = is_null($d['tgl_pengembalian']) ? 'Dipinjam' : 'Dikembalikan';
            if ($d['tgl_pengembalian'] === null && $d['tgl_haruskembali'] < date('Y-m-d H:i:s')) {
                $status = 'Overdue';
            }

            $sheet->setCellValueExplicit('A' . $row, $no, DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B' . $row, (string) ($d['noarsip'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, (string) ($d['uraian'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, (string) ($d['username'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $row, (string) ($d['tgl_pinjam'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $row, (string) ($d['tgl_haruskembali'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $row, (string) ($d['tgl_pengembalian'] ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('H' . $row, $status, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('I' . $row, (string) ($d['ket'] ?? ''), DataType::TYPE_STRING);
            $row++;
            $no++;
        }

        $filename = 'Laporan Sirkulasi ' . date('Y-m-d His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $cachePath = WRITEPATH . 'cache';
        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
        $tempFile = tempnam($cachePath, 'arteri-report-');
        $writer->save($tempFile);
        $body = file_get_contents($tempFile);
        unlink($tempFile);

        return $this->response
            ->download($filename, $body, true)
            ->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setHeader('Content-Transfer-Encoding', 'binary')
            ->setHeader('Content-Length', (string) strlen($body))
            ->setHeader('Cache-Control', 'max-age=0');
    }

    /**
     * Versi cetak (print-to-PDF) laporan arsip.
     * Render halaman print-friendly yang otomatis memanggil window.print()
     * sehingga user dapat menyimpan sebagai PDF lewat dialog browser.
     * GET /report/arsip/print
     */
    public function printArsip()
    {
        $this->logAction('EXPORT', 'report_arsip_pdf', null);

        $keywords     = $this->request->getGet('katakunci') ?? '';
        $kode         = $this->request->getGet('kode') ?? '';
        $tanggal_from = $this->request->getGet('tanggal_from') ?? '';
        $tanggal_to   = $this->request->getGet('tanggal_to') ?? '';
        $ket          = $this->request->getGet('ket') ?? '';

        $arsipModel = new ArsipModel();
        $filters = [
            'noarsip' => '', 'tanggal' => '', 'uraian' => $keywords, 'ket' => $ket,
            'kode' => $kode, 'retensi' => '', 'penc' => '', 'peng' => '', 'lok' => '',
            'med' => '', 'nobox' => '',
        ];

        $results = $arsipModel->search($keywords, $filters, 0, 0);

        $headers = ['No.', 'No.Arsip', 'Tanggal', 'Klasifikasi', 'Uraian', 'Pencipta',
                    'Pengolah', 'Media', 'Lokasi', 'Ket', 'Jumlah', 'No.Box', 'Retensi'];
        $rows = [];
        $no = 1;
        foreach ($results as $d) {
            $rows[] = [
                $no++, $d['noarsip'] ?? '-', $d['tanggal'] ?? '-', $d['nama_kode'] ?? '-',
                $d['uraian'] ?? '-', $d['nama_pencipta'] ?? '-', $d['nama_pengolah'] ?? '-',
                $d['nama_media'] ?? '-', $d['nama_lokasi'] ?? '-', $d['ket'] ?: '-',
                $d['jumlah'] ?? '-', $d['nobox'] ?? '-', $d['b'] ?? '-',
            ];
        }

        return view('report/print', [
            'title'   => 'Laporan Arsip',
            'headers' => $headers,
            'rows'    => $rows,
            'total'   => count($rows),
        ]);
    }

    /**
     * Versi cetak (print-to-PDF) laporan sirkulasi.
     * GET /report/sirkulasi/print
     */
    public function printSirkulasi()
    {
        $this->logAction('EXPORT', 'report_sirkulasi_pdf', null);

        $username     = $this->request->getGet('username') ?? '';
        $status       = $this->request->getGet('status') ?? '';
        $tanggal_from = $this->request->getGet('tanggal_from') ?? '';
        $tanggal_to   = $this->request->getGet('tanggal_to') ?? '';

        $sirkulasiModel = new SirkulasiModel();
        $filters = [
            'username' => $username, 'status' => $status,
            'tanggal_from' => $tanggal_from, 'tanggal_to' => $tanggal_to,
        ];

        $results = $sirkulasiModel->search($username, $filters, 0, 0);

        $headers = ['No.', 'No.Arsip', 'Uraian', 'Peminjam', 'Tgl Pinjam', 'Tgl Harus Kembali',
                    'Tgl Kembali', 'Status', 'Keterangan'];
        $rows = [];
        $no = 1;
        foreach ($results as $d) {
            $rowStatus = is_null($d['tgl_pengembalian']) ? 'Dipinjam' : 'Dikembalikan';
            if (is_null($d['tgl_pengembalian']) && $d['tgl_haruskembali'] < date('Y-m-d H:i:s')) {
                $rowStatus = 'Overdue';
            }
            $rows[] = [
                $no++, $d['noarsip'] ?? '-', $d['uraian'] ?? '-', $d['username'] ?? '-',
                $d['tgl_pinjam'] ?? '-', $d['tgl_haruskembali'] ?? '-',
                $d['tgl_pengembalian'] ?? '-', $rowStatus, $d['ket'] ?? '-',
            ];
        }

        return view('report/print', [
            'title'   => 'Laporan Sirkulasi',
            'headers' => $headers,
            'rows'    => $rows,
            'total'   => count($rows),
        ]);
    }
}