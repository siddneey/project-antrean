<?php

namespace App\Models;

use CodeIgniter\Model;

class antreanModel extends Model
{
    protected $DBGroup = 'transaksi';

    protected $table = 'antrean';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'tanggal_antrean',
        'nomor_antrean',
        'jenis_antrean',
        'instansi_awal_id',
        'waktu_ambil',
    ];

    protected $useTimestamps = true;
}