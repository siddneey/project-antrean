<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Login Admin / Petugas
     */
    public function login(): ResponseInterface
    {
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        // Validasi input
        if ($username === '' || $password === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Username dan password wajib diisi.',
                ]);
        }

        // Cari user berdasarkan username
        $user = $this->userModel
            ->where('username', $username)
            ->first();

        // User tidak ditemukan
        if (!$user) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Username atau password salah.',
                ]);
        }

        // Cek password
        if (!password_verify($password, $user['password'])) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Username atau password salah.',
                ]);
        }

        // Regenerasi session setelah login
        $session = session();
        $session->regenerate(true);

        // Simpan data login ke session
        $session->set([
            'is_logged_in' => true,
            'user_id'      => $user['id'],
            'role_id'      => $user['role_id'],
            'instansi_id'  => $user['instansi_id'],
            'nama'         => $user['nama'],
            'username'     => $user['username'],
        ]);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status'  => true,
                'message' => 'Login berhasil.',
                'data'    => [
                    'user_id'     => $user['id'],
                    'role_id'     => $user['role_id'],
                    'instansi_id' => $user['instansi_id'],
                    'nama'        => $user['nama'],
                    'username'    => $user['username'],
                ],
            ]);
    }

    /**
     * Logout Admin / Petugas
     */
    public function logout(): ResponseInterface
    {
        $session = session();

        $session->destroy();

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status'  => true,
                'message' => 'Logout berhasil.',
            ]);
    }
}