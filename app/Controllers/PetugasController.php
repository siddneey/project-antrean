<?php

namespace App\Controllers;

class PetugasController extends BaseController
{
    public function dashboard()
    {
        return $this->response->setJSON([
            'status'      => true,
            'message'     => 'Dashboard Petugas',
            'instansi_id' => session()->get('instansi_id'),
        ]);
    }
}