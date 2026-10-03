<?php

namespace App\Controllers;

use App\Models\KelompokModel;

class AdminKelompokController extends BaseController
{
    protected KelompokModel $kelompokModel;

    public function __construct()
    {
        $this->kelompokModel = new KelompokModel();
    }

    public function index()
    {
        $kelompok = $this->kelompokModel
            ->select('id, nama_kelompok, created_at, updated_at')
            ->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data'   => $kelompok,
        ]);
    }

    public function create()
    {
        $namaKelompok = trim(
            (string) $this->request->getPost('nama_kelompok')
        );

        if ($namaKelompok === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama kelompok wajib diisi.',
                ]);
        }

        $existing = $this->kelompokModel
            ->where('nama_kelompok', $namaKelompok)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama kelompok sudah digunakan.',
                ]);
        }

        $this->kelompokModel->insert([
            'nama_kelompok' => $namaKelompok,
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => true,
                'message' => 'Kelompok berhasil ditambahkan.',
                'id'      => $this->kelompokModel->getInsertID(),
            ]);
    }

    public function update($id)
    {
        $kelompok = $this->kelompokModel->find($id);

        if (!$kelompok) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Kelompok tidak ditemukan.',
                ]);
        }

        $input = $this->request->getJSON(true);

        $namaKelompok = $input['nama_kelompok'] ?? null;

        if ($namaKelompok === null) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama kelompok wajib diisi.',
                ]);
        }

        $namaKelompok = trim((string) $namaKelompok);

        if ($namaKelompok === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama kelompok tidak boleh kosong.',
                ]);
        }

        $existing = $this->kelompokModel
            ->where('nama_kelompok', $namaKelompok)
            ->where('id !=', $id)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama kelompok sudah digunakan.',
                ]);
        }

        $this->kelompokModel->update($id, [
            'nama_kelompok' => $namaKelompok,
        ]);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Kelompok berhasil diperbarui.',
        ]);
    }

    public function delete($id)
    {
        $kelompok = $this->kelompokModel->find($id);

        if (!$kelompok) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Kelompok tidak ditemukan.',
                ]);
        }

        $this->kelompokModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Kelompok berhasil dihapus.',
        ]);
    }
}