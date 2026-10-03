<?php

namespace App\Models;

use CodeIgniter\Model;

class GrupModel extends Model
{
    protected $DBGroup = 'pusat';

    protected $table = 'grup';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'kelompok_id',
        'nama_grup',
    ];

    protected $useTimestamps = true;
}