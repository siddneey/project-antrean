<?php

namespace App\Controllers;

use App\Models\GrupModel;
use App\Models\InstansiModel;
use App\Models\KelompokModel;

class AdminGrupController extends BaseController
{
    protected GrupModel $grupModel;
    protected KelompokModel $kelompokModel;
    protected InstansiModel $instansiModel;

    public function __construct()
    {
        $this->grupModel = new GrupModel();
        $this->kelompokModel = new KelompokModel();
        $this->instansiModel = new InstansiModel();
    }

    /**
     * GET /admin/grup
     *
     * Menampilkan seluruh grup.
     */
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

    /**
     * POST /admin/grup
     *
     * Menambahkan grup baru.
     */
    public function create()
    {
        $namaGrup = trim(
            (string) $this->request->getPost('nama_grup')
        );

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

        // Nama grup unik di dalam kelompok yang sama
        $existing = $this->grupModel
            ->where('kelompok_id', (int) $kelompokId)
            ->where('nama_grup', $namaGrup)
            ->first();

        if ($existing) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Nama grup sudah digunakan dalam kelompok tersebut.',
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

    /**
     * PUT /admin/grup/{id}
     *
     * Mengubah data grup.
     */
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

        if (!is_array($input)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Request JSON tidak valid.',
                ]);
        }

        $data = [];

        $namaGrup = $input['nama_grup'] ?? null;
        $kelompokId = $input['kelompok_id'] ?? null;

        // Jika kelompok tidak dikirim, gunakan kelompok saat ini
        $targetKelompokId = $kelompokId !== null
            ? (int) $kelompokId
            : (int) $grup['kelompok_id'];

        // Update nama grup
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
                ->where('kelompok_id', $targetKelompokId)
                ->where('nama_grup', $namaGrup)
                ->where('id !=', $id)
                ->first();

            if ($existing) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Nama grup sudah digunakan dalam kelompok tersebut.',
                    ]);
            }

            $data['nama_grup'] = $namaGrup;
        }

        // Update kelompok
        if ($kelompokId !== null) {
            if ($kelompokId < 1) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Kelompok tidak valid.',
                    ]);
            }

            if (!$this->kelompokModel->find($kelompokId)) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Kelompok tidak ditemukan.',
                    ]);
            }

            $data['kelompok_id'] = $targetKelompokId;

            // Jika kelompok berubah tetapi nama tidak dikirim,
            // pastikan nama lama tidak bentrok di kelompok baru.
            if ($namaGrup === null) {
                $existing = $this->grupModel
                    ->where('kelompok_id', $targetKelompokId)
                    ->where('nama_grup', $grup['nama_grup'])
                    ->where('id !=', $id)
                    ->first();

                if ($existing) {
                    return $this->response
                        ->setStatusCode(409)
                        ->setJSON([
                            'status'  => false,
                            'message' => 'Nama grup sudah digunakan dalam kelompok tersebut.',
                        ]);
                }
            }
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

    /**
     * DELETE /admin/grup/{id}
     *
     * Menghapus grup.
     */
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

        // Grup tidak boleh dihapus jika masih memiliki instansi.
        $jumlahInstansi = $this->instansiModel
            ->where('grup_id', $id)
            ->countAllResults();

        if ($jumlahInstansi > 0) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Grup tidak dapat dihapus karena masih memiliki instansi.',
                    'jumlah_instansi' => $jumlahInstansi,
                ]);
        }

        $this->grupModel->delete($id);

        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Grup berhasil dihapus.',
        ]);
    }
}