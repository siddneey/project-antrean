<?php

namespace App\Controllers;

use App\Models\InstansiModel;
use App\Models\LayananModel;
use Config\Database;

class AdminLayananController extends BaseController
{
    protected LayananModel $layananModel;
    protected InstansiModel $instansiModel;

    public function __construct()
    {
        $this->layananModel = new LayananModel();
        $this->instansiModel = new InstansiModel();
    }

    /**
     * GET /admin/layanan
     *
     * Menampilkan seluruh layanan.
     */
    public function index()
    {
        try {
            $layanan = $this->layananModel
                ->select('id, instansi_id, nama_layanan, created_at, updated_at')
                ->findAll();

            return $this->response->setJSON([
                'status' => true,
                'data'   => $layanan,
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminLayananController::index | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal mengambil data layanan.',
                ]);
        }
    }

    /**
     * POST /admin/layanan
     *
     * Menambahkan layanan baru.
     */
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

        if (!filter_var($instansiId, FILTER_VALIDATE_INT)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID instansi tidak valid.',
                ]);
        }

        $instansiId = (int) $instansiId;

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

        try {
            $this->layananModel->insert([
                'instansi_id'  => $instansiId,
                'nama_layanan' => $namaLayanan,
            ]);

            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'status'  => true,
                    'message' => 'Layanan berhasil ditambahkan.',
                    'id'      => $this->layananModel->getInsertID(),
                ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminLayananController::create | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal menambahkan layanan.',
                ]);
        }
    }

    /**
     * PUT /admin/layanan/{id}
     *
     * Mengubah data layanan.
     */
    public function update($id)
    {
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID layanan tidak valid.',
                ]);
        }

        $id = (int) $id;

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

        if (!is_array($input)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Data JSON tidak valid.',
                ]);
        }

        $data = [];

        $namaLayanan = $input['nama_layanan'] ?? null;
        $instansiId  = $input['instansi_id'] ?? null;

        // Update nama layanan
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

        // Update instansi
        if ($instansiId !== null) {
            if (!filter_var($instansiId, FILTER_VALIDATE_INT)) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'ID instansi tidak valid.',
                    ]);
            }

            $instansiId = (int) $instansiId;

            if (!$this->instansiModel->find($instansiId)) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Instansi tidak ditemukan.',
                    ]);
            }

            $data['instansi_id'] = $instansiId;
        }

        if (empty($data)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tidak ada data yang diubah.',
                ]);
        }

        // Nilai akhir setelah update
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

        try {
            $this->layananModel->update($id, $data);

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Layanan berhasil diperbarui.',
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminLayananController::update | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal memperbarui layanan.',
                ]);
        }
    }

    /**
     * DELETE /admin/layanan/{id}
     *
     * Menghapus layanan.
     *
     * Layanan tidak boleh dihapus jika sudah digunakan
     * pada riwayat pelayanan.
     */
    public function delete($id)
    {
        if (!filter_var($id, FILTER_VALIDATE_INT)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID layanan tidak valid.',
                ]);
        }

        $id = (int) $id;

        $layanan = $this->layananModel->find($id);

        if (!$layanan) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Layanan tidak ditemukan.',
                ]);
        }

        try {
            /*
             * riwayat_layanan berada di database mpp_layanan,
             * sedangkan layanan berada di mpp_pusat.
             */
            $dbLayanan = Database::connect('layanan');

            $jumlahRiwayat = $dbLayanan
                ->table('riwayat_layanan')
                ->where('layanan_id', $id)
                ->countAllResults();

            if ($jumlahRiwayat > 0) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Layanan tidak dapat dihapus karena sudah digunakan dalam riwayat pelayanan.',
                    ]);
            }

            $this->layananModel->delete($id);

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Layanan berhasil dihapus.',
            ]);
        } catch (\Throwable $e) {
            log_message(
                'error',
                'AdminLayananController::delete | ' . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal menghapus layanan.',
                ]);
        }
    }
}