<?php

namespace App\Controllers;

use App\Models\InstansiModel;
use App\Models\UserModel;

class AdminPetugasController extends BaseController
{
    protected UserModel $userModel;
    protected InstansiModel $instansiModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->instansiModel = new InstansiModel();
    }

    /**
     * GET /admin/petugas
     *
     * Menampilkan seluruh petugas.
     */
    public function index()
    {
        $petugas = $this->userModel
            ->where('role_id', 2)
            ->select('id, role_id, instansi_id, username, created_at, updated_at')
            ->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data'   => $petugas,
        ]);
    }

    /**
     * POST /admin/petugas
     *
     * Membuat akun petugas baru.
     */
    public function create()
    {
        $username   = trim((string) $this->request->getPost('username'));
        $password   = (string) $this->request->getPost('password');
        $instansiId = $this->request->getPost('instansi_id');

        // Validasi input wajib
        if (
            $username === '' ||
            $password === '' ||
            !$instansiId
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'username, password, dan instansi wajib diisi.',
                ]);
        }

        // Validasi instansi harus ada
        if (!$this->instansiModel->find($instansiId)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Instansi tidak ditemukan.',
                ]);
        }

        // Username tidak boleh duplikat
        if ($this->userModel->where('username', $username)->first()) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'username sudah digunakan.',
                ]);
        }

        // Buat akun petugas
        $this->userModel->insert([
            'role_id'     => 2,
            'instansi_id' => (int) $instansiId,
            'username'    => $username,
            'password'    => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => true,
                'message' => 'Petugas berhasil ditambahkan.',
                'id'      => $this->userModel->getInsertID(),
            ]);
    }

    /**
     * PUT /admin/petugas/{id}
     *
     * Mengubah data petugas.
     */
    public function update($id)
    {
        // Pastikan user adalah petugas
        $petugas = $this->userModel
            ->where('id', $id)
            ->where('role_id', 2)
            ->first();

        if (!$petugas) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Petugas tidak ditemukan.',
                ]);
        }

        $data = [];

        // Data PUT dikirim dalam format JSON
        $input = $this->request->getJSON(true);

        $username   = $input['username'] ?? null;
        $instansiId = $input['instansi_id'] ?? null;
        $password   = $input['password'] ?? null;

        // Update username
        if ($username !== null) {
            $username = trim((string) $username);

            if ($username === '') {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'username tidak boleh kosong.',
                    ]);
            }

            $data['username'] = $username;
        }

        // Update username
        if ($username !== null) {
            $username = trim((string) $username);

            if ($username === '') {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'username tidak boleh kosong.',
                    ]);
            }

            // Cek username dipakai user lain
            $existing = $this->userModel
                ->where('username', $username)
                ->where('id !=', $id)
                ->first();

            if ($existing) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'username sudah digunakan.',
                    ]);
            }

            $data['username'] = $username;
        }

        // Update instansi
        if ($instansiId !== null) {

            // Pastikan instansi benar-benar ada
            if (!$this->instansiModel->find($instansiId)) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Instansi tidak ditemukan.',
                    ]);
            }

            $data['instansi_id'] = (int) $instansiId;
        }

        // Update password jika dikirim
        if ($password !== null && $password !== '') {
            $data['password'] = password_hash(
                (string) $password,
                PASSWORD_DEFAULT
            );
        }

        // Tidak ada data yang diubah
        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        $this->userModel->update($id, $data);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Petugas berhasil diperbarui.',
        ]);
    }

    /**
     * DELETE /admin/petugas/{id}
     *
     * Menghapus akun petugas.
     */
    public function delete($id)
    {
        // Pastikan user adalah petugas
        $petugas = $this->userModel
            ->where('id', $id)
            ->where('role_id', 2)
            ->first();

        if (!$petugas) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Petugas tidak ditemukan.',
                ]);
        }

        $this->userModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Petugas berhasil dihapus.',
        ]);
    }
}