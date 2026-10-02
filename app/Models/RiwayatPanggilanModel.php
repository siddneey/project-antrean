<?php

namespace App\Models;

use CodeIgniter\Model;

class RiwayatPanggilanModel extends Model
{
    protected $DBGroup = 'layanan';

    protected $table = 'riwayat_panggilan';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'riwayat_layanan_id',
        'petugas_id',
        'aksi',
        'waktu',
        'keterangan',
    ];

    protected $useTimestamps = true;
}