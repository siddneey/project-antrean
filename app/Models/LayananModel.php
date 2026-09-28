<?php

namespace App\Models;

use CodeIgniter\Model;

class LayananModel extends Model
{
    protected $table = 'layanan';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'instansi_id',
        'nama_layanan',
    ];

    protected $useTimestamps = true;
}