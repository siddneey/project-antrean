<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TransaksiSeeder extends Seeder
{
    protected $DBGroup = 'transaksi';

    public function run()
    {
        $db = \Config\Database::connect('transaksi');

        // Data testing akan kita isi di sini.
    }
}