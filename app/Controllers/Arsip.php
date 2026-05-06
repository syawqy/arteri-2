<?php

namespace App\Controllers;

use App\Models\ArsipModel;
use App\Models\MasterKodeModel;
use App\Models\MasterPenciptaModel;
use App\Models\MasterPengolahModel;
use App\Models\MasterLokasiModel;
use App\Models\MasterMediaModel;

class Arsip extends BaseController
{
    private const MODULE = 'entridata';

    private function requireAccess(): bool
    {
        if (! hasModuleAccess(self::MODULE)) {
            $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.'])->send();
            return false;
        }
        return true;
    }

    public function new()
    {
        if (! hasModuleAccess(self::MODULE)) {
            return redirect()->to('/');
        }

        $data['title']    = 'Tambah Arsip';
        $data['isEdit']   = false;
        $data['kode2']    = (new MasterKodeModel())->orderBy('kode', 'ASC')->findAll();
        $data['pencipta2'] = (new MasterPenciptaModel())->orderBy('nama_pencipta', 'ASC')->findAll();
        $data['unitpengolah2'] = (new MasterPengolahModel())->orderBy('nama_pengolah', 'ASC')->findAll();
        $data['lokasi2']  = (new MasterLokasiModel())->orderBy('nama_lokasi', 'ASC')->findAll();
        $data['media2']   = (new MasterMediaModel())->orderBy('nama_media', 'ASC')->findAll();

        return view('layout/header', $data)
             . view('arsip/form', $data)
             . view('layout/footer');
    }

    public function create()
    {
        if (! $this->requireAccess()) return;

        $rules = [
            'noarsip'      => 'required|max_length[255]',
            'tanggal'      => 'required|valid_date[Y-m-d]',
            'pencipta'     => 'required|integer',
            'unitpengolah' => 'required|integer',
            'kode'         => 'required|integer',
            'uraian'       => 'required',
            'lokasi'       => 'required|integer',
            'media'        => 'required|integer',
            'ket'          => 'required|in_list[asli,copy]',
            'jumlah'       => 'required|integer|greater_than[0]',
            'nobox'        => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = $this->request->getPost();

        if (! $this->validateForeignKey((int) $post['kode'], new MasterKodeModel(), 'kode')
            || ! $this->validateForeignKey((int) $post['pencipta'], new MasterPenciptaModel(), 'pencipta')
            || ! $this->validateForeignKey((int) $post['unitpengolah'], new MasterPengolahModel(), 'unit pengolah')
            || ! $this->validateForeignKey((int) $post['lokasi'], new MasterLokasiModel(), 'lokasi')
            || ! $this->validateForeignKey((int) $post['media'], new MasterMediaModel(), 'media')) {
            return redirect()->back()->withInput()->with('error', 'Data master terkait tidak ditemukan. Silakan coba lagi.');
        }

        $arsipModel = new ArsipModel();

        $insertData = [
            'noarsip'       => $post['noarsip'],
            'tanggal'       => $post['tanggal'],
            'pencipta'      => (int) $post['pencipta'],
            'unit_pengolah' => (int) $post['unitpengolah'],
            'kode'          => (int) $post['kode'],
            'uraian'        => $post['uraian'],
            'lokasi'        => (int) $post['lokasi'],
            'media'         => (int) $post['media'],
            'ket'           => $post['ket'],
            'jumlah'        => (int) $post['jumlah'],
            'nobox'         => $post['nobox'] ?? '',
            'username'      => session('username') ?? '',
        ];

        $file = $this->request->getFile('file');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR;
            if (! is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['pdf', 'doc', 'docx'], true)) {
                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $insertData['file'] = $newName;
            }
        }

        $insertId = $arsipModel->insert($insertData, true);

        return redirect()->to('/view/' . $insertId)->with('message', 'Arsip berhasil ditambahkan.');
    }

    public function edit($id)
    {
        if (! hasModuleAccess(self::MODULE)) {
            return redirect()->to('/');
        }

        $arsipModel = new ArsipModel();
        $row = $arsipModel->find($id);

        if ($row === null) {
            return redirect()->to('/');
        }

        $data = $row;
        $data['title']         = 'Ubah Arsip';
        $data['isEdit']        = true;
        $data['kode2']         = (new MasterKodeModel())->orderBy('kode', 'ASC')->findAll();
        $data['pencipta2']     = (new MasterPenciptaModel())->orderBy('nama_pencipta', 'ASC')->findAll();
        $data['unitpengolah2'] = (new MasterPengolahModel())->orderBy('nama_pengolah', 'ASC')->findAll();
        $data['lokasi2']       = (new MasterLokasiModel())->orderBy('nama_lokasi', 'ASC')->findAll();
        $data['media2']        = (new MasterMediaModel())->orderBy('nama_media', 'ASC')->findAll();

        $previous = $this->request->getServer('HTTP_REFERER');
        if ($previous) {
            $data['previous'] = $previous;
        }

        return view('layout/header', $data)
             . view('arsip/form', $data)
             . view('layout/footer');
    }

    public function update($id)
    {
        if (! $this->requireAccess()) return;

        $rules = [
            'noarsip'      => 'required|max_length[255]',
            'tanggal'      => 'required|valid_date[Y-m-d]',
            'pencipta'     => 'required|integer',
            'unitpengolah' => 'required|integer',
            'kode'         => 'required|integer',
            'uraian'       => 'required',
            'lokasi'       => 'required|integer',
            'media'        => 'required|integer',
            'ket'          => 'required|in_list[asli,copy]',
            'jumlah'       => 'required|integer|greater_than[0]',
            'nobox'        => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $arsipModel = new ArsipModel();
        $existing = $arsipModel->find($id);

        if ($existing === null) {
            return redirect()->to('/');
        }

        $post = $this->request->getPost();

        if (! $this->validateForeignKey((int) $post['kode'], new MasterKodeModel(), 'kode')
            || ! $this->validateForeignKey((int) $post['pencipta'], new MasterPenciptaModel(), 'pencipta')
            || ! $this->validateForeignKey((int) $post['unitpengolah'], new MasterPengolahModel(), 'unit pengolah')
            || ! $this->validateForeignKey((int) $post['lokasi'], new MasterLokasiModel(), 'lokasi')
            || ! $this->validateForeignKey((int) $post['media'], new MasterMediaModel(), 'media')) {
            return redirect()->back()->withInput()->with('error', 'Data master terkait tidak ditemukan. Silakan coba lagi.');
        }

        $updateData = [
            'noarsip'       => $post['noarsip'],
            'tanggal'       => $post['tanggal'],
            'pencipta'      => (int) $post['pencipta'],
            'unit_pengolah' => (int) $post['unitpengolah'],
            'kode'          => (int) $post['kode'],
            'uraian'        => $post['uraian'],
            'lokasi'        => (int) $post['lokasi'],
            'media'         => (int) $post['media'],
            'ket'           => $post['ket'],
            'jumlah'        => (int) $post['jumlah'],
            'nobox'         => $post['nobox'] ?? '',
        ];

        $file = $this->request->getFile('file');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR;
            if (! is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['pdf', 'doc', 'docx'], true)) {
                if (! empty($existing['file'])) {
                    $oldPath = $uploadPath . $existing['file'];
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $newName = $file->getRandomName();
                $file->move($uploadPath, $newName);
                $updateData['file'] = $newName;
            }
        }

        $arsipModel->update($id, $updateData);

        return redirect()->to('/view/' . $id)->with('message', 'Arsip berhasil diperbarui.');
    }

    public function delete($id)
    {
        if (! $this->requireAccess()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak valid.']);
        }

        $arsipModel = new ArsipModel();
        $row = $arsipModel->find($id);

        if ($row !== null && ! empty($row['file'])) {
            $filePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $row['file'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        $arsipModel->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Arsip berhasil dihapus.']);
    }

    public function deleteFile($id)
    {
        if (! $this->requireAccess()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak valid.']);
        }

        $arsipModel = new ArsipModel();
        $row = $arsipModel->find($id);

        if ($row !== null && ! empty($row['file'])) {
            $filePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $row['file'];
            if (is_file($filePath)) {
                unlink($filePath);
            }

            $arsipModel->update($id, ['file' => null]);
        }

        return $this->response->setJSON(['status' => 'success', 'message' => 'File berhasil dihapus.']);
    }

    private function validateForeignKey(int $id, $model, string $label): bool
    {
        return $model->find($id) !== null;
    }
}
