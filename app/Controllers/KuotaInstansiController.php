<?php

namespace App\Controllers;

use App\Services\AntreanService;

class KuotaInstansiController extends BaseController
{
    protected AntreanService $antreanService;

    public function __construct()
    {
        $this->antreanService = new AntreanService();
    }

    public function index()
    {
        $instansiId = (int) session()->get('instansi_id');

        if ($instansiId < 1) {
            return $this->response
                ->setStatusCode(403)
                ->setJSON([
                    'status' => false,
                    'message' => 'Instansi petugas tidak ditemukan.',
                ]);
        }

        $kuota = $this->antreanService
            ->getKuotaInstansi($instansiId);

        return $this->response->setJSON([
            'status' => true,
            'data' => $kuota,
        ]);
    }

    public function update()
    {
        $instansiId = (int) session()->get('instansi_id');

        $kuotaBiasa = (int) $this->request
            ->getPost('kuota_biasa');

        $kuotaPrioritas = (int) $this->request
            ->getPost('kuota_prioritas');

        if (
            $instansiId < 1 ||
            $kuotaBiasa < 1 ||
            $kuotaPrioritas < 1
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status' => false,
                    'message' => 'Instansi atau nilai kuota tidak valid.',
                ]);
        }

        try {
            $data = $this->antreanService->setKuotaInstansi(
                $instansiId,
                $kuotaBiasa,
                $kuotaPrioritas
            );

            return $this->response->setJSON([
                'status' => true,
                'message' => 'Kuota instansi berhasil diperbarui.',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            log_message('error', $e->getMessage());

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'message' => 'Gagal memperbarui kuota instansi.',
                ]);
        }
    }
}