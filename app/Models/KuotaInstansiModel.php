<?php

namespace App\Models;

use CodeIgniter\Model;

class KuotaInstansiModel extends Model
{
    protected $DBGroup = 'pusat';
    protected $table = 'kuota_instansi';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'instansi_id',
        'tanggal',
        'kuota_biasa',
        'kuota_prioritas',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}