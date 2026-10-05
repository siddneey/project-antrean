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

    /**
     * GET /admin/hari-libur
     *
     * Menampilkan seluruh hari libur.
     */
    public function index()
    {
        try {
            $hariLibur = $this->hariLiburModel
                ->select('id, tanggal, keterangan, created_at, updated_at')
                ->orderBy('tanggal', 'ASC')
                ->findAll();

            return $this->response->setJSON([
                'status' => true,
                'data'   => $hariLibur,
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminHariLiburController::index | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal mengambil data hari libur.',
                ]);
        }
    }

    /**
     * POST /admin/hari-libur
     *
     * Menambahkan hari libur baru.
     */
    public function create()
    {
        $tanggal = trim(
            (string) $this->request->getPost('tanggal')
        );

        $keterangan = trim(
            (string) $this->request->getPost('keterangan')
        );

        if ($tanggal === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tanggal wajib diisi.',
                ]);
        }

        if (!$this->isValidDate($tanggal)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Format tanggal harus YYYY-MM-DD.',
                ]);
        }

        // Cek tanggal duplikat
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

        try {
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
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminHariLiburController::create | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal menambahkan hari libur.',
                ]);
        }
    }

    /**
     * PUT /admin/hari-libur/{id}
     *
     * Mengubah hari libur.
     */
    public function update($id)
    {
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID hari libur tidak valid.',
                ]);
        }

        $id = (int) $id;

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

        if (!is_array($input)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Data JSON tidak valid.',
                ]);
        }

        $data = [];

        // Update tanggal
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

            if (!$this->isValidDate($tanggal)) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Format tanggal harus YYYY-MM-DD.',
                    ]);
            }

            // Cek tanggal duplikat kecuali dirinya sendiri
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

        // Update keterangan
        if (array_key_exists('keterangan', $input)) {
            $keterangan = trim((string) $input['keterangan']);

            $data['keterangan'] = $keterangan !== ''
                ? $keterangan
                : null;
        }

        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        try {
            $this->hariLiburModel->update($id, $data);

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Hari libur berhasil diperbarui.',
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminHariLiburController::update | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memperbarui hari libur.',
                ]);
        }
    }

    /**
     * DELETE /admin/hari-libur/{id}
     *
     * Menghapus hari libur.
     */
    public function delete($id)
    {
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID hari libur tidak valid.',
                ]);
        }

        $id = (int) $id;

        $hariLibur = $this->hariLiburModel->find($id);

        if (!$hariLibur) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Hari libur tidak ditemukan.',
                ]);
        }

        try {
            $this->hariLiburModel->delete($id);

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Hari libur berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminHariLiburController::delete | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal menghapus hari libur.',
                ]);
        }
    }

    /**
     * Validasi tanggal dengan format YYYY-MM-DD.
     */
    protected function isValidDate(string $tanggal): bool
    {
        $tanggalObj = \DateTime::createFromFormat('Y-m-d', $tanggal);

        return $tanggalObj !== false
            && $tanggalObj->format('Y-m-d') === $tanggal;
    }
}