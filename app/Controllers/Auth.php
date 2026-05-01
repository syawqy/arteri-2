<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    public function login()
    {
        // If already logged in, redirect to dashboard
        if (session('username')) {
            return redirect()->to('/');
        }

        $data = [];
        $request = service('request');
        $previous = $request->getServer('HTTP_REFERER');
        if ($previous) {
            $data['previous'] = $previous;
        }

        return view('auth/login', $data);
    }

    public function doLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $previous = $this->request->getPost('previous');

        $userModel = new UserModel();
        $user = $userModel->attemptLogin($username, $password);

        if ($user !== null) {
            // Set session variables
            session()->set('username', $user['username']);
            session()->set('id_user', $user['id']);
            session()->set('tipe', $user['tipe']);
            session()->set('akses_klas', $user['akses_klas']);

            $aksesModul = json_decode($user['akses_modul'], true);
            session()->set('akses_modul', $aksesModul);

            // Compute menu_master: true if user has access to any master module
            $menuMaster = false;
            if (is_array($aksesModul) && count($aksesModul) > 0) {
                $no = 0;
                foreach ($aksesModul as $key => $val) {
                    if ($key === 'klasifikasi') $no++;
                    if ($key === 'pencipta') $no++;
                    if ($key === 'pengolah') $no++;
                    if ($key === 'lokasi') $no++;
                    if ($key === 'media') $no++;
                    if ($key === 'user') $no++;
                    if ($key === 'import') $no++;
                }
                if ($no > 0) {
                    $menuMaster = true;
                }
            }
            session()->set('menu_master', $menuMaster);

            // Redirect to previous page or dashboard
            if ($previous && $previous !== '') {
                return redirect()->to($previous);
            }
            return redirect()->to('/');
        }

        // Login failed
        session()->setFlashdata('erorlogin', 'Username atau password yang ada masukkan salah');
        return redirect()->to('/login');
    }

    public function logout()
    {
        session()->remove('username');
        session()->remove('id_user');
        session()->remove('tipe');
        session()->remove('akses_klas');
        session()->remove('akses_modul');
        session()->remove('menu_master');

        return redirect()->to('/login');
    }
}
