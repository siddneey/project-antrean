<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GrupSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'kelompok_id' => 1,
                'nama_grup'   => 'Grup 1',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 2,
                'nama_grup'   => 'Grup 2',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 2,
                'nama_grup'   => 'Grup 3',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 2,
                'nama_grup'   => 'Grup 4',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 2,
                'nama_grup'   => 'Grup 5',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 1,
                'nama_grup'   => 'Grup 6',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 1,
                'nama_grup'   => 'Bank Banten',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'kelompok_id' => 1,
                'nama_grup'   => 'Bank BJB',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('grup')->insertBatch($data);
    }
}