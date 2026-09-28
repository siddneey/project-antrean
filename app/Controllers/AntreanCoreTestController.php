<?php

namespace App\Controllers;

use App\Services\AntreanService;

class AntreanCoreTestController extends BaseController
{
    protected AntreanService $antreanService;

    public function __construct()
    {
        $this->antreanService = new AntreanService();
    }

    // =========================================================
    // AMBIL ANTREAN
    // =========================================================

    public function ambilBiasa()
    {
        $result = $this->antreanService->ambilAntrean(
            6,              // BAPENDA
            'BIASA',
            date('Y-m-d')   // Tanggal hari ini
        );

        return $this->response->setJSON($result);
    }

    public function ambilPrioritas()
    {
        $result = $this->antreanService->ambilAntrean(
            6,              // BAPENDA
            'PRIORITAS',
            date('Y-m-d')   // Tanggal hari ini
        );

        return $this->response->setJSON($result);
    }


    // =========================================================
    // PANGGIL ANTREAN SELANJUTNYA
    // =========================================================

    public function panggilSelanjutnya()
    {
        $result = $this->antreanService->panggilAntrean(
            3,              // ID PETUGAS
            6               // ID INSTANSI
        );

        return $this->response->setJSON($result);
    }


    // =========================================================
    // PANGGIL ULANG
    // =========================================================

    public function panggilUlang($riwayatLayananId)
    {
        $result = $this->antreanService->panggilUlang(
            3,                      // ID PETUGAS (Harus di depan)
            (int) $riwayatLayananId // ID RIWAYAT LAYANAN
        );

        return $this->response->setJSON($result);
    }


    // =========================================================
    // KONFIRMASI STATUS
    // =========================================================

    public function pending($riwayatLayananId)
    {
        $result = $this->antreanService->konfirmasiStatus(
            3,                      // ID PETUGAS (Harus di depan)
            (int) $riwayatLayananId, // ID RIWAYAT LAYANAN
            'PENDING'
        );

        return $this->response->setJSON($result);
    }


    public function selesai($riwayatLayananId, $layananId)
    {
        $result = $this->antreanService->konfirmasiStatus(
            3,                      // ID PETUGAS (Harus di depan)
            (int) $riwayatLayananId, // ID RIWAYAT LAYANAN
            'SELESAI',
            (int) $layananId,
            'Testing pelayanan selesai'
        );

        return $this->response->setJSON($result);
    }


    // =========================================================
    // PANGGIL PENDING
    // =========================================================

    public function panggilPending($riwayatLayananId)
    {
        $result = $this->antreanService->panggilPending(
            3,                      // ID PETUGAS (Harus di depan)
            (int) $riwayatLayananId // ID RIWAYAT LAYANAN
        );

        return $this->response->setJSON($result);
    }


    // =========================================================
    // DATA ANTREAN
    // =========================================================

    public function sedangDilayani()
    {
        $result = $this->antreanService->getAntreanSedangDilayani(
            6               // ID INSTANSI
        );

        return $this->response->setJSON($result);
    }

    public function antreanSelanjutnya()
    {
        $result = $this->antreanService->getAntreanSelanjutnya(
            6               // ID INSTANSI
        );

        return $this->response->setJSON($result);
    }

    public function sudahDipanggil()
    {
        $result = $this->antreanService->getAntreanSudahDipanggil(
            6               // ID INSTANSI
        );

        return $this->response->setJSON($result);
    }


    // =========================================================
    // TERUSAN ANTREAN
    // =========================================================

    public function terusan($riwayatLayananId, $layananId, $instansiTujuanId)
    {
        $result = $this->antreanService->terusanAntrean(
            3,                      // ID PETUGAS
            (int) $riwayatLayananId, // ID RIWAYAT LAYANAN
            (int) $layananId,       // ID LAYANAN
            (int) $instansiTujuanId, // ID INSTANSI TUJUAN (Harus integer di posisi ke-4)
            'Testing terusan antrean' // Keterangan (String di posisi ke-5)
        );

        return $this->response->setJSON($result);
    }

    public function bookingBesok()
{
    $result = $this->antreanService->ambilAntrean(
        5, // BAPENDA
        'BIASA',
        date('Y-m-d', strtotime('+1 day'))
    );

    return $this->response->setJSON($result);
}
}