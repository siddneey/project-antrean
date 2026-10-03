<?php

namespace App\Controllers;

use App\Models\GrupModel;
use App\Models\InstansiModel;

class AdminInstansiController extends BaseController
{
    protected InstansiModel $instansiModel;
    protected GrupModel $grupModel;

    public function __construct()
    {
        $this->instansiModel = new InstansiModel();
        $this->grupModel = new GrupModel();
    }

    /**
     * GET /admin/instansi
     *
     * Menampilkan seluruh instansi.
     */
    public function index()
    {
        $instansi = $this->instansiModel
            ->select('id, grup_id, nama_instansi, logo, created_at, updated_at')
            ->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data'   => $instansi,
        ]);
    }

    /**
     * POST /admin/instansi
     *
     * Menambahkan instansi baru.
     */
    public function create()
    {
        $namaInstansi = trim(
            (string) $this->request->getPost('nama_instansi')
        );

        $grupId = $this->request->getPost('grup_id');
        $logo   = trim((string) $this->request->getPost('logo'));

        // Validasi input wajib
        if ($namaInstansi === '' || !$grupId) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama instansi dan grup wajib diisi.',
                ]);
        }

        // Validasi grup
        if (!$this->grupModel->find($grupId)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Grup tidak ditemukan.',
                ]);
        }

        // Cek nama instansi duplikat
        $existing = $this->instansiModel
            ->where('nama_instansi', $namaInstansi)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama instansi sudah digunakan.',
                ]);
        }

        // Data instansi
        $data = [
            'grup_id'       => (int) $grupId,
            'nama_instansi' => $namaInstansi,
        ];

        // Logo bersifat opsional
        if ($logo !== '') {
            $data['logo'] = $logo;
        }

        $this->instansiModel->insert($data);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => true,
                'message' => 'Instansi berhasil ditambahkan.',
                'id'      => $this->instansiModel->getInsertID(),
            ]);
    }

    /**
     * PUT /admin/instansi/{id}
     *
     * Mengubah data instansi.
     */
    public function update($id)
    {
        $instansi = $this->instansiModel->find($id);

        if (!$instansi) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Instansi tidak ditemukan.',
                ]);
        }

        // PUT menggunakan JSON
        $input = $this->request->getJSON(true);

        $data = [];

        $namaInstansi = $input['nama_instansi'] ?? null;
        $grupId       = $input['grup_id'] ?? null;
        $logo         = $input['logo'] ?? null;

        // Update nama instansi
        if ($namaInstansi !== null) {
            $namaInstansi = trim((string) $namaInstansi);

            if ($namaInstansi === '') {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Nama instansi tidak boleh kosong.',
                    ]);
            }

            // Cek duplikat kecuali dirinya sendiri
            $existing = $this->instansiModel
                ->where('nama_instansi', $namaInstansi)
                ->where('id !=', $id)
                ->first();

            if ($existing) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Nama instansi sudah digunakan.',
                    ]);
            }

            $data['nama_instansi'] = $namaInstansi;
        }

        // Update grup
        if ($grupId !== null) {
            if (!$this->grupModel->find($grupId)) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Grup tidak ditemukan.',
                    ]);
            }

            $data['grup_id'] = (int) $grupId;
        }

        // Update logo
        if ($logo !== null) {
            $data['logo'] = trim((string) $logo);
        }

        // Tidak ada perubahan
        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        $this->instansiModel->update($id, $data);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Instansi berhasil diperbarui.',
        ]);
    }

    /**
     * DELETE /admin/instansi/{id}
     *
     * Menghapus instansi.
     */
    public function delete($id)
    {
        $instansi = $this->instansiModel->find($id);

        if (!$instansi) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Instansi tidak ditemukan.',
                ]);
        }

        $this->instansiModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Instansi berhasil dihapus.',
        ]);
    }
}