<?php

namespace App\Controllers;

use App\Models\HariLiburModel;

class AdminHariLiburController extends BaseController
{
    protected HariLiburModel $hariLiburModel;

    public function __construct()
    {
        $this->hariLiburModel = new HariLiburModel();
    }

    public function index()
    {
        $hariLibur = $this->hariLiburModel
            ->select('id, tanggal, keterangan, created_at, updated_at')
            ->orderBy('tanggal', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => true,
            'data'   => $hariLibur,
        ]);
    }

    public function create()
    {
        $tanggal    = trim((string) $this->request->getPost('tanggal'));
        $keterangan = trim((string) $this->request->getPost('keterangan'));

        if ($tanggal === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tanggal wajib diisi.',
                ]);
        }

        $tanggalObj = \DateTime::createFromFormat('Y-m-d', $tanggal);

        if (!$tanggalObj || $tanggalObj->format('Y-m-d') !== $tanggal) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Format tanggal harus YYYY-MM-DD.',
                ]);
        }

        $existing = $this->hariLiburModel
            ->where('tanggal', $tanggal)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
                ]);
        }

        $this->hariLiburModel->insert([
            'tanggal'    => $tanggal,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
        ]);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => true,
                'message' => 'Hari libur berhasil ditambahkan.',
                'id'      => $this->hariLiburModel->getInsertID(),
            ]);
    }

    public function update($id)
    {
        $hariLibur = $this->hariLiburModel->find($id);

        if (!$hariLibur) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Hari libur tidak ditemukan.',
                ]);
        }

        $input = $this->request->getJSON(true);
        $data  = [];

        if (array_key_exists('tanggal', $input)) {
            $tanggal = trim((string) $input['tanggal']);

            if ($tanggal === '') {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Tanggal tidak boleh kosong.',
                    ]);
            }

            $tanggalObj = \DateTime::createFromFormat('Y-m-d', $tanggal);

            if (!$tanggalObj || $tanggalObj->format('Y-m-d') !== $tanggal) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Format tanggal harus YYYY-MM-DD.',
                    ]);
            }

            $existing = $this->hariLiburModel
                ->where('tanggal', $tanggal)
                ->where('id !=', $id)
                ->first();

            if ($existing) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Tanggal tersebut sudah terdaftar sebagai hari libur.',
                    ]);
            }

            $data['tanggal'] = $tanggal;
        }

        if (array_key_exists('keterangan', $input)) {
            $keterangan = trim((string) $input['keterangan']);
            $data['keterangan'] = $keterangan !== '' ? $keterangan : null;
        }

        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        $this->hariLiburModel->update($id, $data);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Hari libur berhasil diperbarui.',
        ]);
    }

    public function delete($id)
    {
        $hariLibur = $this->hariLiburModel->find($id);

        if (!$hariLibur) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Hari libur tidak ditemukan.',
                ]);
        }

        $this->hariLiburModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Hari libur berhasil dihapus.',
        ]);
    }
}