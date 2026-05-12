<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class Home extends BaseController
{
    private int $perPage = 20;

    public function index()
    {
        return redirect()->to('search');
    }

    public function search($offset = 0)
    {
        $offset = (int) $offset;
        helper('form');

        $keywords = $this->request->getGet('katakunci') ?? '';
        if ($keywords !== '') {
            $this->logAction('SEARCH', 'data_arsip', null, ['keywords' => $keywords]);
        }
        $this->logPageView('search');

        $arsipModel = new ArsipModel();

        $filters = [
            'noarsip' => $this->request->getGet('noarsip') ?? '',
            'tanggal' => $this->request->getGet('tanggal') ?? '',
            'uraian'  => $this->request->getGet('uraian') ?? '',
            'ket'     => $this->request->getGet('ket') ?? '',
            'kode'    => $this->request->getGet('kode') ?? '',
            'retensi' => $this->request->getGet('retensi') ?? '',
            'penc'    => $this->request->getGet('penc') ?? '',
            'peng'    => $this->request->getGet('peng') ?? '',
            'lok'     => $this->request->getGet('lok') ?? '',
            'med'     => $this->request->getGet('med') ?? '',
            'nobox'   => $this->request->getGet('nobox') ?? '',
        ];

        $results = $arsipModel->search($keywords, $filters, $this->perPage, $offset);
        $total   = $arsipModel->searchCount($keywords, $filters);

        // Source data for view (matches CI3 shape)
        if ($keywords !== '') {
            $src = [
                'noarsip' => '', 'tanggal' => '', 'uraian'  => $keywords,
                'ket'     => '', 'kode'    => '', 'retensi' => '',
                'penc'    => '', 'peng'    => '', 'lok'     => '',
                'med'     => '', 'nobox'   => '',
            ];
        } else {
            $src = $filters;
        }

        $data['kode'] = (new MasterKodeModel())->orderBy('kode', 'ASC')->findAll();
        $data['penc'] = (new MasterPenciptaModel())->orderBy('nama_pencipta', 'ASC')->findAll();
        $data['peng'] = (new MasterPengolahModel())->orderBy('nama_pengolah', 'ASC')->findAll();
        $data['lok']  = (new MasterLokasiModel())->orderBy('nama_lokasi', 'ASC')->findAll();
        $data['med']  = (new MasterMediaModel())->orderBy('nama_media', 'ASC')->findAll();

        $db             = \Config\Database::connect();
        $data['ket']    = $db->table('data_arsip')
            ->select('ket')->distinct()->orderBy('ket', 'ASC')
            ->get()->getResultArray();

        $data['data']     = $results;
        $data['jml']      = $total;
        $data['src']      = $src;

        $page = (int) floor($offset / $this->perPage) + 1;
        $pager = service('pager');
        $pager->setPath('search');
        $pager->makeLinks($page, $this->perPage, $total, 'bootstrap3');
        $data['pager'] = $pager;
        $data['pages'] = $pager->links('default', 'bootstrap3');

        return view('layout/header', $data)
             . view('home/search', $data)
             . view('layout/footer');
    }

    public function detail($id)
    {
        $model = new ArsipModel();
        $data  = $model->getDetail((int) $id);

        if ($data === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Arsip tidak ditemukan');
        }

        if (! hasClassificationAccess((string) ($data['nama_kode'] ?? ''))) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Arsip tidak ditemukan');
        }

        $this->logAction('VIEW_DETAIL', 'data_arsip', (int) $id);

        return view('layout/header', $data)
             . view('home/detail', $data)
             . view('layout/footer');
    }

    public function download()
    {
        $keywords = $this->request->getGet('katakunci') ?? '';
        $this->logAction('DOWNLOAD', 'data_arsip', null, ['keywords' => $keywords]);

        $arsipModel = new ArsipModel();
        $filters = [
            'noarsip' => $this->request->getGet('noarsip') ?? '',
            'tanggal' => $this->request->getGet('tanggal') ?? '',
            'uraian'  => $this->request->getGet('uraian') ?? '',
            'ket'     => $this->request->getGet('ket') ?? '',
            'kode'    => $this->request->getGet('kode') ?? '',
            'retensi' => $this->request->getGet('retensi') ?? '',
            'penc'    => $this->request->getGet('penc') ?? '',
            'peng'    => $this->request->getGet('peng') ?? '',
            'lok'     => $this->request->getGet('lok') ?? '',
            'med'     => $this->request->getGet('med') ?? '',
            'nobox'   => $this->request->getGet('nobox') ?? '',
        ];

        $data = $arsipModel->search($keywords, $filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Arsip');

        $sheet->setCellValue('A1', 'Data Arsip');
        $sheet->getStyle('A1')->getFont()->setSize(14)->setBold(true);
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['No.', 'No.Arsip', 'Tanggal', 'Kode Klasifikasi', 'Uraian', 'Pencipta',
                     'Pengolah', 'Media', 'Lokasi', 'Ket', 'Jumlah', 'No.Box', 'Retensi'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '2', $header);
            $col++;
        }

        $row = 3;
        $no = 1;
        $redStyle = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF0000'],
            ],
        ];

        foreach ($data as $d) {
            $sheet->setCellValue('A' . $row, $no);
            $this->setExportString($sheet, 'B' . $row, $d['noarsip']);
            $this->setExportString($sheet, 'C' . $row, $d['tanggal']);
            $this->setExportString($sheet, 'D' . $row, $d['nama_kode'] ?? '');
            $this->setExportString($sheet, 'E' . $row, $d['uraian']);
            $this->setExportString($sheet, 'F' . $row, $d['nama_pencipta'] ?? '');
            $this->setExportString($sheet, 'G' . $row, $d['nama_pengolah'] ?? '');
            $this->setExportString($sheet, 'H' . $row, $d['nama_media'] ?? '');
            $this->setExportString($sheet, 'I' . $row, $d['nama_lokasi'] ?? '');
            $this->setExportString($sheet, 'J' . $row, $d['ket']);
            $sheet->setCellValue('K' . $row, $d['jumlah']);
            $this->setExportString($sheet, 'L' . $row, $d['nobox']);
            $this->setExportString($sheet, 'M' . $row, $d['b'] ?? '');

            if (($d['f'] ?? '') === 'sudah') {
                $sheet->getStyle('M' . $row)->applyFromArray($redStyle);
            }

            $row++;
            $no++;
        }

        $filename = 'Data Arsip Arteri-' . time() . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function setExportString($sheet, string $cell, mixed $value): void
    {
        $sheet->setCellValueExplicit($cell, (string) ($value ?? ''), DataType::TYPE_STRING);
    }
}
