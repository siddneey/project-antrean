<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(
        RequestInterface $request,
        $arguments = null
    ) {
        $session = session();

        // Cek apakah sudah login
        if (!$session->get('is_logged_in')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Anda belum login.',
                ]);
        }

        // Ambil role user dari session
        $roleId = (int) $session->get('role_id');

        // Cek apakah route menentukan role yang diperbolehkan
        if (empty($arguments)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Role akses tidak ditentukan.',
                ]);
        }

        // Ubah argument role menjadi integer
        $allowedRoles = array_map('intval', $arguments);

        // Cek apakah role user diperbolehkan
        if (!in_array($roleId, $allowedRoles, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => false,
                    'message' => 'Anda tidak memiliki akses.',
                ]);
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Tidak perlu melakukan apa pun.
    }
}