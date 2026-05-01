<?php

namespace App\Controllers;

use App\Models\SirkulasiModel;
use App\Models\ArsipModel;
use App\Models\UserModel;

class Sirkulasi extends BaseController
{
    private int $perPage = 20;

    /**
     * List circulation data with search and pagination.
     */
    public function index()
    {
        $katakunci = $this->request->getGet('katakunci') ?? '';

        $sirkulasiModel = new SirkulasiModel();

        // Pagination using CI4 pager
        $pager = service('pager');
        $pager->setPath('sirkulasi');
        $total = $sirkulasiModel->searchCount($katakunci);
        $pager->makeLinks((int) $pager->getCurrentPage(), $this->perPage, $total, 'bootstrap3');
        $offset = ((int) $pager->getCurrentPage() - 1) * $this->perPage;

        $data = [
            'data'      => $sirkulasiModel->search($katakunci, $this->perPage, $offset),
            'jml'       => $total,
            'katakunci' => $katakunci,
            'admin'     => isAdmin(),
            'title'     => 'Data Sirkulasi',
            'pager'     => $pager,
            'pages'     => $pager->links('default', 'bootstrap3'),
        ];

        echo view('layout/header', $data)
            . view('sirkulasi/index', $data)
            . view('layout/footer');
    }

    /**
     * Show form for a new circulation loan.
     */
    public function new()
    {
        $data = [
            'title'  => 'Peminjaman Arsip',
            'isEdit' => false,
            'now'    => date('Y-m-d'),
        ];

        echo view('layout/header', $data)
            . view('sirkulasi/form', $data)
            . view('layout/footer');
    }

    /**
     * Handle POST for creating a new circulation record.
     */
    public function create()
    {
        $rules = [
            'noarsip'           => 'required|max_length[255]',
            'username_peminjam' => 'required|max_length[255]',
            'keperluan'         => 'required',
            'tgl_pinjam'        => 'required|valid_date[Y-m-d]',
            'tgl_haruskembali'  => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $sirkulasiModel = new SirkulasiModel();

        $sirkulasiModel->insert([
            'noarsip'           => $this->request->getPost('noarsip'),
            'username_peminjam' => $this->request->getPost('username_peminjam'),
            'keperluan'         => $this->request->getPost('keperluan'),
            'tgl_pinjam'        => $this->request->getPost('tgl_pinjam'),
            'tgl_haruskembali'  => $this->request->getPost('tgl_haruskembali'),
            'tgl_transaksi'     => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/sirkulasi');
    }

    /**
     * Show pre-populated edit form.
     *
     * @param int|string $id
     */
    public function edit($id)
    {
        $sirkulasiModel = new SirkulasiModel();
        $row = $sirkulasiModel->find($id);

        if ($row === null) {
            return redirect()->to('/sirkulasi');
        }

        $data = $row;
        $data['title']  = 'Update Data Peminjaman';
        $data['isEdit'] = true;
        $data['now']    = date('Y-m-d');

        $previous = $this->request->getServer('HTTP_REFERER');
        if ($previous) {
            $data['previous'] = $previous;
        }

        echo view('layout/header', $data)
            . view('sirkulasi/form', $data)
            . view('layout/footer');
    }

    /**
     * Handle POST for updating a circulation record.
     *
     * @param int|string $id
     */
    public function update($id)
    {
        $rules = [
            'noarsip'           => 'required|max_length[255]',
            'username_peminjam' => 'required|max_length[255]',
            'keperluan'         => 'required',
            'tgl_pinjam'        => 'required|valid_date[Y-m-d]',
            'tgl_haruskembali'  => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $sirkulasiModel = new SirkulasiModel();
        $existing = $sirkulasiModel->find($id);

        if ($existing === null) {
            return redirect()->to('/sirkulasi');
        }

        $sirkulasiModel->update($id, [
            'noarsip'           => $this->request->getPost('noarsip'),
            'username_peminjam' => $this->request->getPost('username_peminjam'),
            'keperluan'         => $this->request->getPost('keperluan'),
            'tgl_pinjam'        => $this->request->getPost('tgl_pinjam'),
            'tgl_haruskembali'  => $this->request->getPost('tgl_haruskembali'),
        ]);

        return redirect()->to('/sirkulasi');
    }

    /**
     * Handle POST for deleting a circulation record.
     * Supports ID from URL segment or POST body (for modal form submission).
     *
     * @param int|string|null $id
     */
    public function delete($id = null)
    {
        if ($id === null) {
            $id = $this->request->getPost('id');
        }

        $sirkulasiModel = new SirkulasiModel();
        $sirkulasiModel->delete($id);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Handle POST for returning an archive.
     * Supports ID from URL segment or POST body (for modal form submission).
     *
     * @param int|string|null $id
     */
    public function kembali($id = null)
    {
        if ($id === null) {
            $id = $this->request->getPost('id');
        }

        $sirkulasiModel = new SirkulasiModel();
        $existing = $sirkulasiModel->find($id);

        if ($existing === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }

        $sirkulasiModel->returnArchive($id);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * AJAX: Return JSON of archives matching keyword (limit 10).
     *
     * @param string $keywords
     */
    public function xhrArsip($keywords = '')
    {
        if ($keywords === '') {
            return $this->response->setJSON([]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('data_arsip');
        $builder->select('noarsip, kode, nobox');
        $builder->groupStart()
            ->like('noarsip', $keywords)
            ->orLike('kode', $keywords)
            ->groupEnd();
        $builder->limit(10);

        $results = $builder->get()->getResultArray();

        return $this->response->setJSON($results);
    }

    /**
     * AJAX: Return JSON of users matching keyword (limit 10).
     *
     * @param string $keywords
     */
    public function xhrUser($keywords = '')
    {
        if ($keywords === '') {
            return $this->response->setJSON([]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('master_user');
        $builder->select('username, id, tipe, akses_klas');
        $builder->like('username', $keywords);
        $builder->limit(10);

        $results = $builder->get()->getResultArray();

        return $this->response->setJSON($results);
    }
}
