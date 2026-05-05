<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\Throttle\Throttler;

class Auth extends Controller
{
    public function login()
    {
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

        if (! $this->validate('login')) {
            session()->setFlashdata('error_login', 'Username dan password harus diisi.');
            return redirect()->to('/login')->withInput();
        }

        $ip = $this->request->getIPAddress();

        $db = \Config\Database::connect();
        $fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $recentAttempts = $db->table('login_attempts')
            ->where('username', $username)
            ->where('attempted_at >=', $fifteenMinutesAgo)
            ->countAllResults();

        if ($recentAttempts >= 5) {
            session()->setFlashdata('error_login', 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.');
            return redirect()->to('/login')->withInput();
        }

        $db->table('login_attempts')->insert([
            'username'     => $username,
            'ip_address'   => $ip,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);

        $userModel = new UserModel();
        $user = $userModel->attemptLogin($username, $password);

        if ($user !== null) {
            $this->logLoginAttempt($username, true);

            session()->regenerate(true);

            session()->set('username', $user['username']);
            session()->set('id_user', $user['id']);
            session()->set('tipe', $user['tipe']);
            session()->set('akses_klas', $user['akses_klas']);

            $aksesModul = json_decode($user['akses_modul'], true);
            session()->set('akses_modul', $aksesModul);

            $menuMaster = false;
            if (is_array($aksesModul) && count($aksesModul) > 0) {
                $masterModules = ['klasifikasi', 'pencipta', 'pengolah', 'lokasi', 'media', 'user', 'import'];
                foreach ($masterModules as $mod) {
                    if (array_key_exists($mod, $aksesModul)) {
                        $menuMaster = true;
                        break;
                    }
                }
            }
            session()->set('menu_master', $menuMaster);

            if ($previous && $previous !== '') {
                return redirect()->to($previous);
            }
            return redirect()->to('/');
        }

        $this->logLoginAttempt($username, false);

        session()->setFlashdata('error_login', 'Username atau password yang Anda masukkan salah.');
        return redirect()->to('/login')->withInput();
    }

    public function logout()
    {
        $this->logLoginAttempt(session('username') ?? 'unknown', true, 'LOGOUT');

        session()->destroy();
        session()->regenerate(true);

        return redirect()->to('/login');
    }

    private function logLoginAttempt(string $username, bool $success, string $overrideAksi = ''): void
    {
        $log = new \App\Models\SystemLogModel();
        $aksi = $overrideAksi ?: ($success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED');
        $detail = null;
        if (! $success && ! $overrideAksi) {
            $detail = ['reason' => 'invalid_credentials'];
        }

        $log->insert([
            'kode_transaksi'     => $aksi,
            'username_transaksi' => $username,
            'tgl_transaksi'      => date('Y-m-d H:i:s'),
            'aksi'               => $aksi,
            'tabel'              => 'master_user',
            'record_id'          => null,
            'detail'             => $detail ? json_encode($detail) : null,
            'ip_address'         => $this->request->getIPAddress(),
        ]);
    }
}
