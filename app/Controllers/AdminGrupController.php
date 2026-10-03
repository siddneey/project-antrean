<?php

namespace App\Controllers;

use App\Models\GrupModel;
use App\Models\KelompokModel;

class AdminGrupController extends BaseController
{
    protected GrupModel $grupModel;
    protected KelompokModel $kelompokModel;

    public function __construct()
    {
        $this->grupModel = new GrupModel();
        $this->kelompokModel = new KelompokModel();
    }

    public function index()
    {
        $grup = $this->grupModel
            ->select('id, kelompok_id, nama_grup, created_at, updated_at')
            ->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data'   => $grup,
        ]);
    }

    public function create()
    {
        $namaGrup   = trim((string) $this->request->getPost('nama_grup'));
        $kelompokId = $this->request->getPost('kelompok_id');

        if ($namaGrup === '' || !$kelompokId) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama grup dan kelompok wajib diisi.',
                ]);
        }

        // Validasi kelompok
        if (!$this->kelompokModel->find($kelompokId)) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Kelompok tidak ditemukan.',
                ]);
        }

        // Cek duplikasi nama grup
        $existing = $this->grupModel
            ->where('nama_grup', $namaGrup)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama grup sudah digunakan.',
                ]);
        }

        $this->grupModel->insert([
            'kelompok_id' => (int) $kelompokId,
            'nama_grup'   => $namaGrup,
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => true,
                'message' => 'Grup berhasil ditambahkan.',
                'id'      => $this->grupModel->getInsertID(),
            ]);
    }

    public function update($id)
    {
        $grup = $this->grupModel->find($id);

        if (!$grup) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Grup tidak ditemukan.',
                ]);
        }

        $input = $this->request->getJSON(true);

        $data = [];

        $namaGrup   = $input['nama_grup'] ?? null;
        $kelompokId = $input['kelompok_id'] ?? null;

        if ($namaGrup !== null) {
            $namaGrup = trim((string) $namaGrup);

            if ($namaGrup === '') {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Nama grup tidak boleh kosong.',
                    ]);
            }

            $existing = $this->grupModel
                ->where('nama_grup', $namaGrup)
                ->where('id !=', $id)
                ->first();

            if ($existing) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Nama grup sudah digunakan.',
                    ]);
            }

            $data['nama_grup'] = $namaGrup;
        }

        if ($kelompokId !== null) {
            // Validasi kelompok
            if (!$this->kelompokModel->find($kelompokId)) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Kelompok tidak ditemukan.',
                    ]);
            }

            $data['kelompok_id'] = (int) $kelompokId;
        }

        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        $this->grupModel->update($id, $data);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Grup berhasil diperbarui.',
        ]);
    }

    public function delete($id)
    {
        $grup = $this->grupModel->find($id);

        if (!$grup) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Grup tidak ditemukan.',
                ]);
        }

        $this->grupModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Grup berhasil dihapus.',
        ]);
    }
}