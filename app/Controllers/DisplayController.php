<?php

namespace App\Controllers;

use App\Services\DisplayService;
use RuntimeException;
use Throwable;

class DisplayController extends BaseController
{
    protected DisplayService $displayService;

    public function __construct()
    {
        $this->displayService = new DisplayService();
    }

    /**
     * GET /display/kelompok/{id}
     *
     * Menampilkan data display berdasarkan kelompok.
     *
     * Kelompok berfungsi sebagai zona display/audio.
     */
    public function kelompok($id)
    {
        $kelompokId = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        if (
            $kelompokId === false
            || $kelompokId < 1
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID kelompok tidak valid.',
                ]);
        }

        try {
            $data = $this->displayService
                ->getDisplayByKelompok($kelompokId);

            return $this->response->setJSON([
                'status' => true,
                'data'   => $data,
            ]);

        } catch (RuntimeException $e) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => false,
                    'message' => $e->getMessage(),
                ]);

        } catch (Throwable $e) {
            log_message(
                'error',
                'DisplayController::kelompok | '
                . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal mengambil data display.',
                ]);
        }
    }
}
