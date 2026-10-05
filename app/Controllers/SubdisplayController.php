<?php

namespace App\Controllers;

use App\Services\SubdisplayService;
use RuntimeException;
use Throwable;

class SubdisplayController extends BaseController
{
    protected SubdisplayService $subdisplayService;

    public function __construct()
    {
        $this->subdisplayService = new SubdisplayService();
    }

    /**
     * GET /subdisplay/grup/{id}
     *
     * Menampilkan data subdisplay berdasarkan grup.
     */
    public function grup($id)
    {
        $grupId = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        if (
            $grupId === false
            || $grupId < 1
        ) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status'  => false,
                    'message' => 'ID grup tidak valid.',
                ]);
        }

        try {
            $data = $this->subdisplayService
                ->getSubdisplayByGrup($grupId);

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
                'SubdisplayController::grup | '
                . $e->getMessage()
            );

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Gagal mengambil data subdisplay.',
                ]);
        }
    }
}