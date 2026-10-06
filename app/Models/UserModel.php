<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $DBGroup = 'pusat';

    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'role_id',
        'instansi_id',
        'username',
        'password',
    ];

    protected $useTimestamps = true;
}