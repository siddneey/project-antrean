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

    /**
     * GET /petugas/antrean/sedang-dilayani
     */
    public function sedangDilayani()
    {
        $instansiId = (int) session()->get('instansi_id');

        try {
            $data = $this->antreanService
                ->getAntreanSedangDilayani($instansiId);

            return $this->response->setJSON([
                'status' => true,
                'data'   => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * GET /petugas/antrean/menunggu
     */
    public function menunggu()
    {
        $instansiId = (int) session()->get('instansi_id');

        try {
            $data = $this->antreanService
                ->getAntreanMenunggu($instansiId);

            return $this->response->setJSON([
                'status' => true,
                'data'   => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /petugas/antrean/panggil
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
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /petugas/antrean/panggil-ulang
     */
    public function panggilUlang()
    {
        $petugasId = (int) session()->get('user_id');
        $input = $this->request->getJSON(true) ?? [];

        $riwayatLayananId = (int) (
            $input['riwayat_layanan_id'] ?? 0
        );

        if ($riwayatLayananId <= 0) {
            return $this->badRequest(
                'riwayat_layanan_id wajib diisi.'
            );
        }

        try {
            $data = $this->antreanService
                ->panggilUlang(
                    $riwayatLayananId,
                    $petugasId
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean berhasil dipanggil ulang.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /petugas/antrean/status
     *
     * status:
     * - SELESAI
     * - PENDING
     */
    public function konfirmasiStatus()
    {
        $petugasId = (int) session()->get('user_id');
        $input = $this->request->getJSON(true) ?? [];

        $riwayatLayananId = (int) (
            $input['riwayat_layanan_id'] ?? 0
        );

        $status = strtoupper(
            trim((string) ($input['status'] ?? ''))
        );

        $layananId = null;

        if (array_key_exists('layanan_id', $input)) {
            $layananId = (int) $input['layanan_id'];
        }

        $keterangan = array_key_exists('keterangan', $input)
            ? trim((string) $input['keterangan'])
            : null;

        if ($riwayatLayananId <= 0) {
            return $this->badRequest(
                'riwayat_layanan_id wajib diisi.'
            );
        }

        if (!in_array($status, ['SELESAI', 'PENDING'], true)) {
            return $this->badRequest(
                'status harus SELESAI atau PENDING.'
            );
        }

        if ($status === 'SELESAI' && $layananId <= 0) {
            return $this->badRequest(
                'layanan_id wajib diisi untuk status SELESAI.'
            );
        }

        try {
            $data = $this->antreanService
              
            ->konfirmasiStatus(
                    $riwayatLayananId,
                    $petugasId,
                    $status,
                    $layananId,
                    $keterangan
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => $status === 'SELESAI'
                    ? 'Antrean berhasil diselesaikan.'
                    : 'Antrean berhasil dipindahkan ke pending.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /petugas/antrean/panggil-pending
     */
    public function panggilPending()
    {
        $petugasId = (int) session()->get('user_id');
        $input = $this->request->getJSON(true) ?? [];

        $riwayatLayananId = (int) (
            $input['riwayat_layanan_id'] ?? 0
        );

        if ($riwayatLayananId <= 0) {
            return $this->badRequest(
                'riwayat_layanan_id wajib diisi.'
            );
        }

        try {
            $data = $this->antreanService
                ->panggilPending(
                    $riwayatLayananId,
                    $petugasId
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean pending berhasil dipanggil.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * GET /petugas/antrean/sudah-dipanggil
     */
    public function sudahDipanggil()
    {
        $instansiId = (int) session()->get('instansi_id');

        try {
            $data = $this->antreanService
                ->getAntreanSudahDipanggil($instansiId);

            return $this->response->setJSON([
                'status' => true,
                'data'   => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /petugas/antrean/terusan
     */
    public function terusan()
    {
        $petugasId = (int) session()->get('user_id');
        $input = $this->request->getJSON(true) ?? [];

        $riwayatLayananId = (int) (
            $input['riwayat_layanan_id'] ?? 0
        );

        $instansiTujuanId = (int) (
            $input['instansi_tujuan_id'] ?? 0
        );

        $keterangan = array_key_exists('keterangan', $input)
            ? trim((string) $input['keterangan'])
            : null;

        if ($riwayatLayananId <= 0) {
            return $this->badRequest(
                'riwayat_layanan_id wajib diisi.'
            );
        }

        if ($instansiTujuanId <= 0) {
            return $this->badRequest(
                'instansi_tujuan_id wajib diisi.'
            );
        }

        try {
            $data = $this->antreanService
                ->terusanAntrean(
                    $riwayatLayananId,
                    $petugasId,
                    $instansiTujuanId,
                    $keterangan
                );

            return $this->response->setJSON([
                'status'  => true,
                'message' => 'Antrean berhasil diteruskan.',
                'data'    => $data,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Response error bisnis/validasi.
     */
    protected function errorResponse(Throwable $e)
    {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'status'  => false,
                'message' => $e->getMessage(),
            ]);
    }

    /**
     * Response bad request.
     */
    protected function badRequest(string $message)
    {
        return $this->response
            ->setStatusCode(400)
            ->setJSON([
                'status'  => false,
                'message' => $message,
            ]);
    }
}