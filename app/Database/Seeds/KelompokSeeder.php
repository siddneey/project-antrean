<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KelompokSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'nama_kelompok' => 'Kelompok 1',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
            [
                'nama_kelompok' => 'Kelompok 2',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('kelompok')->insertBatch($data);
    }
}