<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $password = password_hash('password123', PASSWORD_DEFAULT);

        $data = [
            [
                'role_id'     => 1,
                'instansi_id' => null,
                'nama'        => 'Administrator',
                'username'    => 'admin',
                'password'    => $password,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_id'     => 2,
                'instansi_id' => 5,
                'nama'        => 'Petugas BAPENDA',
                'username'    => 'petugas_bapenda',
                'password'    => $password,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'role_id'     => 2,
                'instansi_id' => 6,
                'nama'        => 'Petugas BPKD',
                'username'    => 'petugas_bpkd',
                'password'    => $password,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}