<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class Import extends BaseController
{
    public function index(): void
    {
        helper('acl');
        echo view('layout/header', ['title' => 'Import Data']);
        echo view('import/index');
        echo view('layout/footer');
    }

    public function doImport(): void
    {
        $file = $this->request->getFile('up_file');
        if (!$file || !$file->isValid()) {
            session()->setFlashdata('zz', 'Tidak ada file yang diupload');
            redirect()->to('/import');
        }

        try {
            $reader = IOFactory::createReaderForFile($file->getTempName());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getTempName());
            $sheets = $spreadsheet->getSheetNames();

            $arsipModel = new ArsipModel();

            foreach ($sheets as $sheetName) {
                $sheet = $spreadsheet->setActiveSheetIndexByName($sheetName);
                $maxRow = $sheet->getHighestRow();
                $maxCol = $sheet->getHighestColumn();
                $allCol = range('A', $maxCol);

                // Row 2 = field names
                $fields = [];
                foreach ($allCol as $col) {
                    $val = $sheet->getCell($col . '2')->getCalculatedValue();
                    $fields[] = $val;
                }

                // Rows 3+ = data
                for ($i = 3; $i <= $maxRow; $i++) {
                    $rowData = [];
                    foreach ($allCol as $k => $col) {
                        $rowData[$fields[$k]] = $sheet->getCell($col . $i)->getCalculatedValue();
                    }

                    $noarsip   = $rowData['No.Arsip'] ?? '';
                    $tanggal   = $rowData['Tanggal'] ?? '';
                    $uraian    = $rowData['Uraian'] ?? '';
                    $ket       = $rowData['Ket'] ?? '';
                    $nobox     = $rowData['No.Box'] ?? '';
                    $jumlah    = $rowData['Jumlah'] ?? 1;
                    $username  = $rowData['username'] ?? session('username');

                    if (empty($noarsip) && empty($uraian)) {
                        continue;
                    }

                    // Resolve master IDs from names
                    $idKode = $this->resolveMasterId('kode', $rowData['Kode Klasifikasi'] ?? '');
                    $idPenc = $this->resolveMasterId('pencipta', $rowData['Pencipta'] ?? '');
                    $idPeng = $this->resolveMasterId('pengolah', $rowData['Pengolah'] ?? '');
                    $idLok  = $this->resolveMasterId('lokasi', $rowData['Lokasi'] ?? '');
                    $idMed  = $this->resolveMasterId('media', $rowData['Media'] ?? '');

                    $arsipModel->insert([
                        'noarsip'       => $noarsip,
                        'tanggal'       => $tanggal,
                        'uraian'        => $uraian,
                        'kode'          => $idKode,
                        'ket'           => $ket,
                        'nobox'         => $nobox,
                        'file'          => '',
                        'jumlah'        => (int) $jumlah,
                        'pencipta'      => $idPenc,
                        'unit_pengolah' => $idPeng,
                        'lokasi'        => $idLok,
                        'media'         => $idMed,
                        'username'      => $username,
                    ]);
                }
            }

            session()->setFlashdata('zz', 'Data berhasil diimport');
        } catch (\Exception $e) {
            session()->setFlashdata('zz', 'Gagal import: ' . $e->getMessage());
        }

        redirect()->to('/import');
    }

    private function resolveMasterId(string $type, string $name): string
    {
        if (empty($name)) {
            return '';
        }

        $model = match ($type) {
            'kode'     => new MasterKodeModel(),
            'pencipta' => new MasterPenciptaModel(),
            'pengolah' => new MasterPengolahModel(),
            'lokasi'   => new MasterLokasiModel(),
            'media'    => new MasterMediaModel(),
            default    => null,
        };

        if (!$model) {
            return '';
        }

        // For kode, search by 'kode' field; for others search by name field
        if ($type === 'kode') {
            $existing = $model->where('kode', $name)->first();
            if ($existing) {
                return (string) $existing['id'];
            }
            $model->insert(['kode' => $name, 'nama' => $name, 'retensi' => 0]);
            return (string) $model->insertID();
        }

        $nameField = match ($type) {
            'pencipta' => 'nama_pencipta',
            'pengolah' => 'nama_pengolah',
            'lokasi'   => 'nama_lokasi',
            'media'    => 'nama_media',
            default    => 'nama',
        };

        $existing = $model->where($nameField, $name)->first();
        if ($existing) {
            return (string) $existing['id'];
        }

        $model->insert([$nameField => $name]);
        return (string) $model->insertID();
    }
}
