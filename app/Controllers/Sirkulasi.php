<?php

namespace App\Controllers;

use App\Models\SirkulasiModel;
use App\Models\ArsipModel;
use App\Models\UserModel;

class Sirkulasi extends BaseController
{
    private int $perPage = 20;
    private const MODULE = 'sirkulasi';

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

        $katakunci = $this->request->getGet('katakunci') ?? '';

        $sirkulasiModel = new SirkulasiModel();

        $pager = service('pager');
        $pager->setPath('sirkulasi');
        $total = $sirkulasiModel->searchCount($katakunci);
        $pager->makeLinks((int) $pager->getCurrentPage(), $this->perPage, $total, 'bootstrap3');
        $offset = ((int) $pager->getCurrentPage() - 1) * $this->perPage;

        $this->logPageView('sirkulasi/index');

        $data = [
            'data'      => $sirkulasiModel->search($katakunci, $this->perPage, $offset),
            'jml'       => $total,
            'katakunci' => $katakunci,
            'admin'     => isAdmin(),
            'title'     => 'Data Sirkulasi',
            'pager'     => $pager,
            'pages'     => $pager->links('default', 'bootstrap3'),
        ];

        return view('sirkulasi/index', $data);
    }

    public function new()
    {
        if (! $this->requireAccess()) {
            return redirect()->to('/');
        }

        $this->logPageView('sirkulasi/new');

        $data = [
            'title'  => 'Peminjaman Arsip',
            'isEdit' => false,
            'now'    => date('Y-m-d'),
        ];

        return view('sirkulasi/form', $data);
    }

    public function create()
    {
        if (! $this->requireAccess()) {
            return redirect()->to('/');
        }

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

        $arsipModel = new ArsipModel();
        $arsip = $arsipModel->where('noarsip', $this->request->getPost('noarsip'))->first();
        if ($arsip === null) {
            return redirect()->back()->withInput()->with('error', 'Arsip dengan nomor tersebut tidak ditemukan.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('username', $this->request->getPost('username_peminjam'))->first();
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Pengguna tidak ditemukan.');
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
        $insertId = $sirkulasiModel->getInsertID();

        $this->logAction('CREATE', 'sirkulasi', $insertId);

        return redirect()->to('/sirkulasi')->with('message', 'Peminjaman berhasil dicatat.');
    }

    public function edit($id)
    {
        if (! $this->requireAccess()) {
            return redirect()->to('/');
        }

        $sirkulasiModel = new SirkulasiModel();
        $row = $sirkulasiModel->find($id);

        if ($row === null) {
            return redirect()->to('/sirkulasi');
        }

        $this->logPageView('sirkulasi/edit');

        $data = $row;
        $data['title']  = 'Update Data Peminjaman';
        $data['isEdit'] = true;
        $data['now']    = date('Y-m-d');

        $previous = $this->request->getServer('HTTP_REFERER');
        if ($previous) {
            $data['previous'] = $previous;
        }

        return view('sirkulasi/form', $data);
    }

    public function update($id)
    {
        if (! $this->requireAccess()) {
            return redirect()->to('/');
        }

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

        $arsipModel = new ArsipModel();
        $arsip = $arsipModel->where('noarsip', $this->request->getPost('noarsip'))->first();
        if ($arsip === null) {
            return redirect()->back()->withInput()->with('error', 'Arsip dengan nomor tersebut tidak ditemukan.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('username', $this->request->getPost('username_peminjam'))->first();
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', 'Pengguna tidak ditemukan.');
        }

        $sirkulasiModel->update($id, [
            'noarsip'           => $this->request->getPost('noarsip'),
            'username_peminjam' => $this->request->getPost('username_peminjam'),
            'keperluan'         => $this->request->getPost('keperluan'),
            'tgl_pinjam'        => $this->request->getPost('tgl_pinjam'),
            'tgl_haruskembali'  => $this->request->getPost('tgl_haruskembali'),
        ]);

        $this->logAction('UPDATE', 'sirkulasi', (int) $id);

        return redirect()->to('/sirkulasi')->with('message', 'Peminjaman berhasil diperbarui.');
    }

    public function delete($id = null)
    {
        if (! $this->requireAccess()) return;

        if ($id === null) {
            $id = $this->request->getPost('id');
        }
        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak valid.']);
        }

        $sirkulasiModel = new SirkulasiModel();
        $sirkulasiModel->delete($id);

        $this->logAction('DELETE', 'sirkulasi', $id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Sirkulasi berhasil dihapus.']);
    }

    public function kembali($id = null)
    {
        if (! $this->requireAccess()) return;

        if ($id === null) {
            $id = $this->request->getPost('id');
        }
        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak valid.']);
        }

        $sirkulasiModel = new SirkulasiModel();
        $existing = $sirkulasiModel->find($id);

        if ($existing === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak ditemukan.']);
        }

        $sirkulasiModel->returnArchive($id);

        $this->logAction('KEMBALI', 'sirkulasi', $id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Arsip berhasil dikembalikan.']);
    }

    public function xhrArsip($keywords = '')
    {
        if (! $this->requireAccess()) return;

        if (empty($keywords)) {
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

    public function xhrUser($keywords = '')
    {
        if (! $this->requireAccess()) return;

        if (empty($keywords)) {
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
