<?php

namespace App\Controllers;

use App\Services\AntreanService;
use Throwable;

class PetugasAntreanController extends BaseController
{
    protected AntreanService $antreanService;

    public function __construct()
    {
        $this->antreanService = new AntreanService();
    }

    /*
    |--------------------------------------------------------------------------
    | ANTREAN SEDANG DILAYANI
    |--------------------------------------------------------------------------
    */

    public function sedangDilayani()
    {
        $instansiId = (int) session()->get('instansi_id');

        $data = $this->antreanService
            ->getAntreanSedangDilayani($instansiId);

        return $this->response->setJSON([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ANTREAN MENUNGGU
    |--------------------------------------------------------------------------
    */

    public function menunggu()
    {
        $instansiId = (int) session()->get('instansi_id');

        $data = $this->antreanService
            ->getAntreanMenunggu($instansiId);

        return $this->response->setJSON([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PANGGIL ANTREAN BERIKUTNYA
    |--------------------------------------------------------------------------
    */

    public function panggil()
    {
        $petugasId  = (int) session()->get('user_id');
        $instansiId = (int) session()->get('instansi_id');

        try {
            $data = $this->antreanService
                ->panggilAntrean(
                    $petugasId,
                    $instansiId
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean berhasil dipanggil.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PANGGIL ULANG
    |--------------------------------------------------------------------------
    */

    public function panggilUlang()
    {
        $petugasId = (int) session()->get('user_id');

        $input = $this->request->getJSON(true);

        $riwayatLayananId = (int) ($input['riwayat_layanan_id'] ?? 0);

        if ($riwayatLayananId <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'riwayat_layanan_id wajib diisi.',
                ]);
        }

        try {
            $data = $this->antreanService
                ->panggilUlang(
                    $petugasId,
                    $riwayatLayananId
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean berhasil dipanggil ulang.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | KONFIRMASI STATUS
    |--------------------------------------------------------------------------
    |
    | status:
    | - SELESAI
    | - PENDING
    |
    */

    public function konfirmasiStatus()
    {
        $petugasId = (int) session()->get('user_id');

        $input = $this->request->getJSON(true);

        $riwayatLayananId = (int) ($input['riwayat_layanan_id'] ?? 0);
        $status            = trim((string) ($input['status'] ?? ''));
        $layananId         = isset($input['layanan_id'])
            ? (int) $input['layanan_id']
            : null;
        $keterangan        = isset($input['keterangan'])
            ? trim((string) $input['keterangan'])
            : null;

        if ($riwayatLayananId <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'riwayat_layanan_id wajib diisi.',
                ]);
        }

        if ($status === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'status wajib diisi.',
                ]);
        }

        try {
            $data = $this->antreanService
                ->konfirmasiStatus(
                    $petugasId,
                    $riwayatLayananId,
                    $status,
                    $layananId,
                    $keterangan
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Status antrean berhasil diperbarui.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PANGGIL ANTREAN PENDING
    |--------------------------------------------------------------------------
    */

    public function panggilPending()
    {
        $petugasId = (int) session()->get('user_id');

        $input = $this->request->getJSON(true);

        $riwayatLayananId = (int) ($input['riwayat_layanan_id'] ?? 0);

        if ($riwayatLayananId <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'riwayat_layanan_id wajib diisi.',
                ]);
        }

        try {
            $data = $this->antreanService
                ->panggilPending(
                    $petugasId,
                    $riwayatLayananId
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean pending berhasil dipanggil.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ANTREAN SUDAH DIPANGGIL / RIWAYAT AKTIVITAS
    |--------------------------------------------------------------------------
    */

    public function sudahDipanggil()
    {
        $instansiId = (int) session()->get('instansi_id');

        $data = $this->antreanService
            ->getAntreanSudahDipanggil($instansiId);

        return $this->response->setJSON([
            'status' => true,
            'data'   => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TERUSAN ANTREAN
    |--------------------------------------------------------------------------
    */

    public function terusan()
    {
        $petugasId = (int) session()->get('user_id');

        $input = $this->request->getJSON(true);

        $riwayatLayananId = (int) ($input['riwayat_layanan_id'] ?? 0);
        $layananId        = (int) ($input['layanan_id'] ?? 0);
        $instansiTujuanId = (int) ($input['instansi_tujuan_id'] ?? 0);
        $keterangan       = isset($input['keterangan'])
            ? trim((string) $input['keterangan'])
            : null;

        if ($riwayatLayananId <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'riwayat_layanan_id wajib diisi.',
                ]);
        }

        if ($layananId <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'layanan_id wajib diisi.',
                ]);
        }

        if ($instansiTujuanId <= 0) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'instansi_tujuan_id wajib diisi.',
                ]);
        }

        try {
            $data = $this->antreanService
                ->terusanAntrean(
                    $petugasId,
                    $riwayatLayananId,
                    $layananId,
                    $instansiTujuanId,
                    $keterangan
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean berhasil diteruskan.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);
        }
    }
}