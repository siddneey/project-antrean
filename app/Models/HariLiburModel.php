<?php

namespace App\Models;

use CodeIgniter\Model;

class HariLiburModel extends Model
{
    protected $DBGroup = 'pusat';

    protected $table = 'hari_libur';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'tanggal',
        'keterangan',
    ];

    protected $useTimestamps = true;
}