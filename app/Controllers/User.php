<?php

namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController
{
    /**
     * List all users with optional search.
     */
    public function index()
    {
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

    /**
     * Create a new user.
     */
    public function create()
    {
        $password     = $this->request->getPost('password');
        $confPassword = $this->request->getPost('conf_password');

        if ($password !== $confPassword) {
            return $this->response->setJSON([
                'status' => 'error',
                'pesan'  => 'PASSWORD_UNMATCH',
            ]);
        }

        $model = new UserModel();

        // Check username uniqueness
        $existing = $model->where('username', $this->request->getPost('username'))->first();
        if ($existing) {
            return $this->response->setJSON([
                'status' => 'error',
                'pesan'  => 'USERNAME_EXISTS',
            ]);
        }

        $model->insert([
            'username'     => $this->request->getPost('username'),
            'password'     => password_hash($password, PASSWORD_BCRYPT),
            'tipe'         => $this->request->getPost('tipe'),
            'akses_klas'   => $this->request->getPost('akses_klas') ?? '',
            'akses_modul'  => json_encode($this->request->getPost('modul') ?? []),
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Update an existing user.
     */
    public function update()
    {
        $model = new UserModel();

        $data = [
            'username'    => $this->request->getPost('username'),
            'tipe'        => $this->request->getPost('tipe'),
            'akses_klas'  => $this->request->getPost('akses_klas') ?? '',
            'akses_modul' => json_encode($this->request->getPost('modul') ?? []),
        ];

        $password = $this->request->getPost('password');
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $model->update((int) $this->request->getPost('id'), $data);

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Delete a user. Prevent deleting the last admin.
     */
    public function delete()
    {
        $model = new UserModel();
        $user  = $model->find((int) $this->request->getPost('id'));

        if (! $user) {
            return $this->response->setJSON(['status' => 'error', 'pesan' => 'User not found']);
        }

        // Prevent deleting the last admin
        if ($user['tipe'] === 'admin') {
            $adminCount = $model->where('tipe', 'admin')->countAllResults();
            if ($adminCount <= 1) {
                return $this->response->setJSON([
                    'status' => 'error',
                    'pesan'  => 'Tidak dapat menghapus admin terakhir',
                ]);
            }
        }

        $model->delete((int) $this->request->getPost('id'));

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Get a single user as JSON.
     */
    public function get()
    {
        $model = new UserModel();
        $user  = $model->find((int) $this->request->getPost('id'));

        return $this->response->setJSON($user ?? []);
    }

    /**
     * Check if a username already exists.
     */
    public function cekUsername()
    {
        $username = $this->request->getPost('username');
        $model    = new UserModel();
        $existing = $model->where('username', $username)->first();

        if ($existing) {
            return $this->response->setJSON(['msg' => 'error']);
        }

        return $this->response->setJSON(['msg' => 'ok']);
    }

    /**
     * AJAX reload: return filtered HTML table.
     */
    public function reload()
    {
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

    /**
     * Build HTML table string for AJAX reload.
     */
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
            $html .= '<td><a data-toggle="modal" data-target="#edituser" class="eduser" href="#" id="' . esc($u['id'], 'attr') . '" title="Edit"><i class="glyphicon glyphicon-edit"></i> </a></td>';
            $html .= '<td><a data-toggle="modal" data-target="#deluser" class="deluser" href="#" id="' . esc($u['id'], 'attr') . '" title="Delete"><i class="glyphicon glyphicon-trash"></i> </a></td>';
            $html .= '</tr>';
            $no++;
        }

        $html .= '</table>';
        return $html;
    }
}
