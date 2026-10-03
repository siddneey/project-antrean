<?php

namespace App\Models;

use CodeIgniter\Model;

class KelompokModel extends Model
{
    protected $DBGroup = 'pusat';

    protected $table = 'kelompok';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nama_kelompok',
    ];

    protected $useTimestamps = true;
}