<?php

namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController
{
    private function requireAccess(): bool
    {
        if (! hasModuleAccess('user')) {
            $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.'])->send();
            return false;
        }
        return true;
    }

    public function index()
    {
        if (! hasModuleAccess('user')) {
            return redirect()->to('/');
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';

        $model = new UserModel();
        $builder = $model->builder();

        if ($katakunci !== '') {
            $builder->groupStart()
                ->like('username', $katakunci)
                ->orLike('tipe', $katakunci)
                ->groupEnd();
        }

        $users = $builder->orderBy('username', 'ASC')->get()->getResultArray();

        $data = [
            'users'     => $users,
            'katakunci' => $katakunci,
            'title'     => 'Data User',
        ];

        return view('layout/header', $data)
            . view('user/index', $data)
            . view('layout/footer');
    }

    public function create()
    {
        if (! $this->requireAccess()) return;

        $rules = [
            'username'      => 'required|max_length[255]|string',
            'password'      => 'required|min_length[8]|regex_match[/[a-zA-Z]/]|regex_match[/[0-9]/]',
            'conf_password' => 'required|matches[password]',
            'tipe'          => 'required|in_list[admin,user]',
        ];

        if (! $this->validate($rules)) {
            return $this->response->setJSON($this->formatValidationErrors($this->validator->getErrors()));
        }

        $model = new UserModel();

        $existing = $model->where('username', $this->request->getPost('username'))->first();
        if ($existing) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Username sudah digunakan.']);
        }

        $model->insert([
            'username'     => $this->request->getPost('username'),
            'password'     => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'tipe'         => $this->request->getPost('tipe'),
            'akses_klas'   => $this->request->getPost('akses_klas') ?? '',
            'akses_modul'  => json_encode($this->request->getPost('modul') ?? []),
        ]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'User berhasil dibuat.']);
    }

    public function update()
    {
        if (! $this->requireAccess()) return;

        $id = (int) $this->request->getPost('id');

        $rules = [
            'id'       => 'required|integer',
            'username' => 'required|max_length[255]|string',
            'tipe'     => 'required|in_list[admin,user]',
        ];

        $password = $this->request->getPost('password');
        if (! empty($password)) {
            $rules['password'] = 'min_length[8]|regex_match[/[a-zA-Z]/]|regex_match[/[0-9]/]';
            $rules['conf_password'] = 'required_with[password]|matches[password]';
        }

        if (! $this->validate($rules)) {
            return $this->response->setJSON($this->formatValidationErrors($this->validator->getErrors()));
        }

        $model = new UserModel();
        $existing = $model->find($id);
        if ($existing === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User tidak ditemukan.']);
        }

        $data = [
            'username'    => $this->request->getPost('username'),
            'tipe'        => $this->request->getPost('tipe'),
            'akses_klas'  => $this->request->getPost('akses_klas') ?? '',
            'akses_modul' => json_encode($this->request->getPost('modul') ?? []),
        ];

        if (! empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $model->update($id, $data);

        return $this->response->setJSON(['status' => 'success', 'message' => 'User berhasil diperbarui.']);
    }

    public function delete()
    {
        if (! $this->requireAccess()) return;

        $id = (int) $this->request->getPost('id');

        if (! $this->validate(['id' => 'required|integer'])) {
            return $this->response->setJSON($this->formatValidationErrors($this->validator->getErrors()));
        }

        $model = new UserModel();
        $user  = $model->find($id);

        if (! $user) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'User tidak ditemukan.']);
        }

        if ($user['tipe'] === 'admin') {
            $adminCount = $model->where('tipe', 'admin')->countAllResults();
            if ($adminCount <= 1) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak dapat menghapus admin terakhir.']);
            }
        }

        $model->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'User berhasil dihapus.']);
    }

    public function get()
    {
        if (! $this->requireAccess()) return;

        $id = (int) $this->request->getPost('id');

        if (! $this->validate(['id' => 'required|integer'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'ID tidak valid.']);
        }

        $model = new UserModel();
        $user  = $model->find($id);

        return $this->response->setJSON($user ?? []);
    }

    public function cekUsername()
    {
        if (! $this->requireAccess()) return;

        $username = $this->request->getPost('username');
        if (empty($username)) {
            return $this->response->setJSON(['msg' => 'error', 'message' => 'Username tidak boleh kosong.']);
        }

        $model    = new UserModel();
        $existing = $model->where('username', $username)->first();

        if ($existing) {
            return $this->response->setJSON(['msg' => 'error', 'message' => 'Username sudah digunakan.']);
        }

        return $this->response->setJSON(['msg' => 'ok']);
    }

    public function reload()
    {
        if (! hasModuleAccess('user')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $katakunci = $this->request->getGet('katakunci') ?? '';

        $model = new UserModel();
        $builder = $model->builder();

        if ($katakunci !== '') {
            $builder->groupStart()
                ->like('username', $katakunci)
                ->orLike('tipe', $katakunci)
                ->groupEnd();
        }

        $users = $builder->orderBy('username', 'ASC')->get()->getResultArray();

        return $this->renderTable($users);
    }

    protected function renderTable(array $users): string
    {
        $html = '<table class="table table-bordered" name="vuser" id="vuser">';
        $html .= '<thead>';
        $html .= '<th class="width-sm">No</th>';
        $html .= '<th>Username</th>';
        $html .= '<th>Akses Klasifikasi</th>';
        $html .= '<th>Akses Modul</th>';
        $html .= '<th>Tipe</th>';
        $html .= '<th class="width-sm"></th>';
        $html .= '<th class="width-sm"></th>';
        $html .= '</thead>';

        if (empty($users)) {
            $html .= '</table>';
            return $html;
        }

        $no = 1;
        foreach ($users as $u) {
            $html .= '<tr>';
            $html .= '<td>' . $no . '</td>';
            $html .= '<td>' . esc($u['username']) . '</td>';
            $html .= '<td>' . esc($u['akses_klas']) . '</td>';
            $html .= '<td>';

            $mm = $u['akses_modul'];
            if ($mm !== '') {
                $decoded = json_decode($mm, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $key => $val) {
                        $html .= esc($key) . ',';
                    }
                }
            }

            $html .= '</td>';
            $html .= '<td>' . esc($u['tipe']) . '</td>';
            if (hasModuleAccess('user')) {
                $html .= '<td><a data-toggle="modal" data-target="#edituser" class="eduser" href="#" id="' . esc($u['id'], 'attr') . '" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>';
                $html .= '<td><a data-toggle="modal" data-target="#deluser" class="deluser" href="#" id="' . esc($u['id'], 'attr') . '" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>';
            } else {
                $html .= '<td></td><td></td>';
            }
            $html .= '</tr>';
            $no++;
        }

        $html .= '</table>';
        return $html;
    }
}
