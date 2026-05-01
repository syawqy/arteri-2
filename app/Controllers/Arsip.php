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
    /**
     * Show archive entry form.
     */
    public function new()
    {
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

    /**
     * Process archive entry form.
     */
    public function create()
    {
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
            'jumlah'       => 'required|integer',
            'nobox'        => 'permit_empty|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $arsipModel = new ArsipModel();

        $insertData = [
            'noarsip'       => $this->request->getPost('noarsip'),
            'tanggal'       => $this->request->getPost('tanggal'),
            'pencipta'      => (int) $this->request->getPost('pencipta'),
            'unit_pengolah' => (int) $this->request->getPost('unitpengolah'),
            'kode'          => (int) $this->request->getPost('kode'),
            'uraian'        => $this->request->getPost('uraian'),
            'lokasi'        => (int) $this->request->getPost('lokasi'),
            'media'         => (int) $this->request->getPost('media'),
            'ket'           => $this->request->getPost('ket'),
            'jumlah'        => (int) $this->request->getPost('jumlah'),
            'nobox'         => $this->request->getPost('nobox') ?? '',
            'username'      => session('username') ?? '',
        ];

        // File upload
        $file = $this->request->getFile('file');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            $uploadPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR;
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

        return redirect()->to('/view/' . $insertId);
    }

    /**
     * Show archive edit form.
     *
     * @param int|string $id
     */
    public function edit($id)
    {
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

    /**
     * Process archive edit form.
     *
     * @param int|string $id
     */
    public function update($id)
    {
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
            'jumlah'       => 'required|integer',
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

        $updateData = [
            'noarsip'       => $this->request->getPost('noarsip'),
            'tanggal'       => $this->request->getPost('tanggal'),
            'pencipta'      => (int) $this->request->getPost('pencipta'),
            'unit_pengolah' => (int) $this->request->getPost('unitpengolah'),
            'kode'          => (int) $this->request->getPost('kode'),
            'uraian'        => $this->request->getPost('uraian'),
            'lokasi'        => (int) $this->request->getPost('lokasi'),
            'media'         => (int) $this->request->getPost('media'),
            'ket'           => $this->request->getPost('ket'),
            'jumlah'        => (int) $this->request->getPost('jumlah'),
            'nobox'         => $this->request->getPost('nobox') ?? '',
        ];

        // File upload
        $file = $this->request->getFile('file');
        if ($file !== null && $file->isValid() && ! $file->hasMoved()) {
            $uploadPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR;
            if (! is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $ext = strtolower($file->getExtension());
            if (in_array($ext, ['pdf', 'doc', 'docx'], true)) {
                // Delete old file if exists
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

        return redirect()->to('/view/' . $id);
    }

    /**
     * Delete an archive record and its file.
     *
     * @param int|string $id
     */
    public function delete($id)
    {
        $arsipModel = new ArsipModel();
        $row = $arsipModel->find($id);

        if ($row !== null && ! empty($row['file'])) {
            $filePath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $row['file'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }

        $arsipModel->delete($id);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Delete only the attached file, keep the record.
     *
     * @param int|string $id
     */
    public function deleteFile($id)
    {
        $arsipModel = new ArsipModel();
        $row = $arsipModel->find($id);

        if ($row !== null && ! empty($row['file'])) {
            $filePath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'arsip' . DIRECTORY_SEPARATOR . $row['file'];
            if (is_file($filePath)) {
                unlink($filePath);
            }

            $arsipModel->update($id, ['file' => null]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }
}
