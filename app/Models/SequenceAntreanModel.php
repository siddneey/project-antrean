<?php

namespace App\Models;

use CodeIgniter\Model;

class SequenceAntreanModel extends Model
{
    protected $DBGroup = 'transaksi';

    protected $table = 'sequence_antrean';
    protected $primaryKey = 'tanggal';

    protected $allowedFields = [
        'tanggal',
        'nomor_terakhir',
        'updated_at',
    ];

    protected $useTimestamps = false;
}