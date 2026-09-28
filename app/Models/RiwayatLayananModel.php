<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatLayananModel extends Model
{
    protected $DBGroup = 'transaksi';

    protected $table = 'riwayat_layanan';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'antrean_id',
        'instansi_id',
        'layanan_id',
        'petugas_id',
        'status_layanan',
        'waktu_masuk',
        'waktu_mulai',
        'waktu_selesai',
        'keterangan',
    ];

    protected $useTimestamps = true;
}