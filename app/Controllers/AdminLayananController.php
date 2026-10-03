<?php

namespace App\Controllers;

use App\Models\InstansiModel;
use App\Models\LayananModel;

class AdminLayananController extends BaseController
{
    protected LayananModel $layananModel;
    protected InstansiModel $instansiModel;

    public function __construct()
    {
        $this->layananModel = new LayananModel();
        $this->instansiModel = new InstansiModel();
    }

    public function index()
    {
        $layanan = $this->layananModel
            ->select('id, instansi_id, nama_layanan, created_at, updated_at')
            ->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data'   => $layanan,
        ]);
    }

    public function create()
    {
        $namaLayanan = trim(
            (string) $this->request->getPost('nama_layanan')
        );

        $instansiId = $this->request->getPost('instansi_id');

        if ($namaLayanan === '' || !$instansiId) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama layanan dan instansi wajib diisi.',
                ]);
        }

        // Validasi instansi
        if (!$this->instansiModel->find($instansiId)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Instansi tidak ditemukan.',
                ]);
        }

        // Nama layanan unik dalam instansi yang sama
        $existing = $this->layananModel
            ->where('instansi_id', $instansiId)
            ->where('nama_layanan', $namaLayanan)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama layanan sudah digunakan pada instansi tersebut.',
                ]);
        }

        $this->layananModel->insert([
            'instansi_id'  => (int) $instansiId,
            'nama_layanan' => $namaLayanan,
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => true,
                'message' => 'Layanan berhasil ditambahkan.',
                'id'      => $this->layananModel->getInsertID(),
            ]);
    }

    public function update($id)
    {
        $layanan = $this->layananModel->find($id);

        if (!$layanan) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Layanan tidak ditemukan.',
                ]);
        }

        $input = $this->request->getJSON(true);

        $data = [];

        $namaLayanan = $input['nama_layanan'] ?? null;
        $instansiId  = $input['instansi_id'] ?? null;

        if ($namaLayanan !== null) {
            $namaLayanan = trim((string) $namaLayanan);

            if ($namaLayanan === '') {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Nama layanan tidak boleh kosong.',
                    ]);
            }

            $data['nama_layanan'] = $namaLayanan;
        }

        if ($instansiId !== null) {
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

        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        // Tentukan nilai akhir setelah update
        $finalInstansiId = $data['instansi_id'] ?? $layanan['instansi_id'];
        $finalNamaLayanan = $data['nama_layanan'] ?? $layanan['nama_layanan'];

        // Cek duplikasi pada instansi tujuan
        $existing = $this->layananModel
            ->where('instansi_id', $finalInstansiId)
            ->where('nama_layanan', $finalNamaLayanan)
            ->where('id !=', $id)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama layanan sudah digunakan pada instansi tersebut.',
                ]);
        }

        $this->layananModel->update($id, $data);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Layanan berhasil diperbarui.',
        ]);
    }

    public function delete($id)
    {
        $layanan = $this->layananModel->find($id);

        if (!$layanan) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Layanan tidak ditemukan.',
                ]);
        }

        $this->layananModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Layanan berhasil dihapus.',
        ]);
    }
}