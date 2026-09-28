<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InstansiSeeder extends Seeder
{
    public function run()
    {
        // 1. Matikan pemeriksaan foreign key sementara khusus untuk MySQL/MariaDB
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0;');

        // 2. Kosongkan tabel instansi (dan reset ID ke 1)
        // Jika tabel layanan atau users ikut terikat dan mau dibersihkan juga, bisa di-truncate di sini.
        // Tapi kalau hanya ingin mengosongkan instansi, cukup tabel ini saja:
        $this->db->table('instansi')->truncate();

        // 3. Nyalakan kembali pemeriksaan foreign key
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1;');

        $data = [
            // Grup 1
            [
                'grup_id'       => 1,
                'nama_instansi' => 'SAMSAT',
            ],
            [
                'grup_id'       => 1,
                'nama_instansi' => 'BPJS KESEHATAN',
            ],
            [
                'grup_id'       => 1,
                'nama_instansi' => 'DISNAKER',
            ],
            [
                'grup_id'       => 1,
                'nama_instansi' => 'ATR BPN',
            ],

            // Grup 2
            [
                'grup_id'       => 2,
                'nama_instansi' => 'BAPENDA',
            ],
            [
                'grup_id'       => 2,
                'nama_instansi' => 'BPKD',
            ],
            [
                'grup_id'       => 2,
                'nama_instansi' => 'BPJS TK',
            ],
            [
                'grup_id'       => 2,
                'nama_instansi' => 'KEJARI',
            ],

            // Grup 3
            [
                'grup_id'       => 3,
                'nama_instansi' => 'DUKCAPIL',
            ],
            [
                'grup_id'       => 3,
                'nama_instansi' => 'DITJEN AHU',
            ],
            [
                'grup_id'       => 3,
                'nama_instansi' => 'PENGADILAN AGAMA',
            ],
            [
                'grup_id'       => 3,
                'nama_instansi' => 'PELAYANAN PBG',
            ],

            // Grup 4
            [
                'grup_id'       => 4,
                'nama_instansi' => 'DPUPR',
            ],
            [
                'grup_id'       => 4,
                'nama_instansi' => 'PERKIM',
            ],
            [
                'grup_id'       => 4,
                'nama_instansi' => 'DISHUB',
            ],
            [
                'grup_id'       => 4,
                'nama_instansi' => 'BNN',
            ],

            // Grup 5
            [
                'grup_id'       => 5,
                'nama_instansi' => 'POLRES',
            ],
            [
                'grup_id'       => 5,
                'nama_instansi' => 'PT. POS INDONESIA',
            ],
            [
                'grup_id'       => 5,
                'nama_instansi' => 'TASPEN',
            ],
            [
                'grup_id'       => 5,
                'nama_instansi' => 'PLN',
            ],
            [
                'grup_id'       => 5,
                'nama_instansi' => 'PDAM',
            ],

            // Grup 6
            [
                'grup_id'       => 6,
                'nama_instansi' => 'BPOM',
            ],
            [
                'grup_id'       => 6,
                'nama_instansi' => 'DINAS LINGKUNGAN HIDUP',
            ],
            [
                'grup_id'       => 6,
                'nama_instansi' => 'PTSP 1',
            ],
            [
                'grup_id'       => 6,
                'nama_instansi' => 'PTSP 2',
            ],

            // Bank Banten
            [
                'grup_id'       => 7,
                'nama_instansi' => 'BANK BANTEN',
            ],

            // Bank BJB
            [
                'grup_id'       => 8,
                'nama_instansi' => 'BANK BJB',
            ],
            [
                'grup_id'       => 8,
                'nama_instansi' => 'Baznas',
            ],
        ];

        foreach ($data as &$item) {
            $item['created_at'] = date('Y-m-d H:i:s');
            $item['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('instansi')->insertBatch($data);
    }
}