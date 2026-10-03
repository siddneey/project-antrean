<?php

namespace App\Controllers;

class AdminController extends BaseController
{
    public function dashboard()
    {
        return $this->response->setJSON([
            'status'  => true,
            'message' => 'Dashboard Admin',
        ]);
    }
}