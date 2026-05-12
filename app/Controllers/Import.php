<?php

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
    private const MODULE = 'import';

    private function requireAccess(): bool
    {
        if (! hasModuleAccess(self::MODULE)) {
            return false;
        }
        return true;
    }

    public function index()
    {
        if (! $this->requireAccess()) {
            return redirect()->to('/');
        }

        helper('acl');
        $this->logPageView('import/index');

        return view('layout/header', ['title' => 'Import Data'])
             . view('import/index')
             . view('layout/footer');
    }

    public function doImport()
    {
        if (! $this->requireAccess()) {
            return redirect()->to('/');
        }

        $file = $this->request->getFile('up_file');
        if (!$file || !$file->isValid()) {
            session()->setFlashdata('error', 'Tidak ada file yang diupload.');
            return redirect()->to('/import');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xls', 'xlsx'], true)) {
            session()->setFlashdata('error', 'Format file tidak didukung. Gunakan .xls atau .xlsx.');
            return redirect()->to('/import');
        }

        try {
            $readerType = $ext === 'xls' ? 'Xls' : 'Xlsx';
            $reader = IOFactory::createReader($readerType);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getTempName());
            $sheets = $spreadsheet->getSheetNames();

            $arsipModel = new ArsipModel();
            $errors = [];
            $insertedCount = 0;

            foreach ($sheets as $sheetName) {
                $sheet = $spreadsheet->setActiveSheetIndexByName($sheetName);
                $maxRow = $sheet->getHighestRow();
                $maxCol = $sheet->getHighestColumn();
                $allCol = range('A', $maxCol);

                $fields = [];
                foreach ($allCol as $col) {
                    $val = $sheet->getCell($col . '2')->getCalculatedValue();
                    $fields[] = $val;
                }

                for ($i = 3; $i <= $maxRow; $i++) {
                    $rowData = [];
                    foreach ($allCol as $k => $col) {
                        $rowData[$fields[$k]] = $sheet->getCell($col . $i)->getCalculatedValue();
                    }

                    $noarsip   = trim($rowData['No.Arsip'] ?? '');
                    $uraian    = trim($rowData['Uraian'] ?? '');
                    $username  = $rowData['username'] ?? session('username');

                    if (empty($noarsip) && empty($uraian)) {
                        continue;
                    }

                    if (empty($noarsip)) {
                        $errors[] = "Baris {$i}: No. Arsip harus diisi.";
                        continue;
                    }

                    $tanggal = $rowData['Tanggal'] ?? date('Y-m-d');
                    if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $tanggal)) {
                        $errors[] = "Baris {$i}: Format tanggal tidak valid (gunakan YYYY-MM-DD).";
                        continue;
                    }

                    $jumlah = (int) ($rowData['Jumlah'] ?? 1);
                    if ($jumlah < 1) {
                        $errors[] = "Baris {$i}: Jumlah harus lebih dari 0.";
                        continue;
                    }

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
                        'ket'           => $rowData['Ket'] ?? '',
                        'nobox'         => $rowData['No.Box'] ?? '',
                        'file'          => '',
                        'jumlah'        => $jumlah,
                        'pencipta'      => $idPenc,
                        'unit_pengolah' => $idPeng,
                        'lokasi'        => $idLok,
                        'media'         => $idMed,
                        'username'      => $username,
                    ]);

                    $insertedCount++;
                }
            }

            $message = "{$insertedCount} data berhasil diimport.";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' baris error: ' . implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= ' ...dan ' . (count($errors) - 5) . ' error lainnya.';
                }
            }
            $this->logAction('IMPORT', 'data_arsip', null, [
                'inserted' => $insertedCount,
                'errors'   => count($errors),
            ]);

            session()->setFlashdata('message', $message);
        } catch (\Exception $e) {
            $this->logAction('IMPORT_FAILED', 'data_arsip', null, ['error' => $e->getMessage()]);
            session()->setFlashdata('error', 'Gagal import: ' . $e->getMessage());
        }
        return redirect()->to('/import');
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
