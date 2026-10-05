<?php

namespace App\Controllers;

use App\Services\AntreanService;
use Throwable;

class MasyarakatController extends BaseController
{
    protected AntreanService $antreanService;

    public function __construct()
    {
        $this->antreanService = new AntreanService();
    }

    /**
     * GET /masyarakat/instansi
     *
     * Menampilkan daftar instansi yang tersedia
     * untuk dipilih masyarakat.
     */
    public function instansi()
    {
        try {
            $dbPusat = db_connect('pusat');

            $instansi = $dbPusat
                ->table('instansi')
                ->select('id, grup_id, nama_instansi, logo')
                ->orderBy('nama_instansi', 'ASC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'status' => true,
                'data'   => $instansi,
            ]);
        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal mengambil daftar instansi.',
                ]);
        }
    }

    /**
     * GET /masyarakat/kuota/{instansiId}?tanggal=YYYY-MM-DD
     *
     * Menampilkan kuota dan jumlah antrean yang sudah terpakai.
     */
    public function kuota($instansiId)
    {
        $instansiId = (int) $instansiId;

        if ($instansiId < 1) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Instansi tidak valid.',
                ]);
        }

        $tanggal = trim(
            (string) $this->request->getGet('tanggal')
        );

        if ($tanggal === '') {
            $tanggal = date('Y-m-d');
        }

        try {
            $dbPusat = db_connect('pusat');

            $instansi = $dbPusat
                ->table('instansi')
                ->select('id, nama_instansi, logo')
                ->where('id', $instansiId)
                ->get()
                ->getRowArray();

            if (!$instansi) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status'  => false,
                        'message' => 'Instansi tidak ditemukan.',
                    ]);
            }

            $kuota = $this->antreanService
                ->getKuotaInstansi(
                    $instansiId,
                    $tanggal
                );

            $terpakaiBiasa = $this->antreanService
                ->getJumlahAntreanInstansi(
                    $instansiId,
                    'BIASA',
                    $tanggal
                );

            $terpakaiPrioritas = $this->antreanService
                ->getJumlahAntreanInstansi(
                    $instansiId,
                    'PRIORITAS',
                    $tanggal
                );

            return $this->response->setJSON([
                'status' => true,
                'data'   => [
                    'instansi_id' => $instansiId,
                    'instansi'    => $instansi['nama_instansi'],
                    'logo'        => $instansi['logo'] ?? null,
                    'tanggal'     => $tanggal,

                    'biasa' => [
                        'kuota'    => $kuota['kuota_biasa'],
                        'terpakai' => $terpakaiBiasa,
                        'tersisa'  => max(
                            0,
                            $kuota['kuota_biasa']
                                - $terpakaiBiasa
                        ),
                    ],

                    'prioritas' => [
                        'kuota'    => $kuota['kuota_prioritas'],
                        'terpakai' => $terpakaiPrioritas,
                        'tersisa'  => max(
                            0,
                            $kuota['kuota_prioritas']
                                - $terpakaiPrioritas
                        ),
                    ],
                ],
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

    /**
     * POST /masyarakat/antrean
     *
     * Body JSON:
     * {
     *     "tanggal_antrean": "2026-10-05",
     *     "instansi_id": 5,
     *     "jenis_antrean": "PRIORITAS"
     * }
     *
     * Masyarakat hanya memilih instansi.
     * Layanan ditentukan oleh petugas saat pelayanan.
     */
    public function ambilAntrean()
    {
        $input = $this->request->getJSON(true);

        if (!is_array($input)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Request JSON tidak valid.',
                ]);
        }

        $tanggal = trim(
            (string) ($input['tanggal_antrean'] ?? '')
        );

        $instansiId = filter_var(
            $input['instansi_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $jenisAntrean = strtoupper(
            trim((string) ($input['jenis_antrean'] ?? ''))
        );

        if ($tanggal === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Tanggal antrean wajib diisi.',
                ]);
        }

        if ($instansiId === false || $instansiId < 1) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Instansi wajib dipilih.',
                ]);
        }

        if (!in_array(
            $jenisAntrean,
            ['BIASA', 'PRIORITAS'],
            true
        )) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Jenis antrean harus BIASA atau PRIORITAS.',
                ]);
        }

        try {
            $hasil = $this->antreanService->ambilAntrean(
                (int) $instansiId,
                $jenisAntrean,
                $tanggal
            );

            return $this->response
                ->setStatusCode(201)
                ->setJSON($hasil);
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