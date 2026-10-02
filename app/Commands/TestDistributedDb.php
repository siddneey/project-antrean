<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class TestDistributedDb extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:test-distributed';
    protected $description = 'Test koneksi ke tiga database terdistribusi.';

    public function run(array $params)
    {
        $connections = [
            'mpp_antrean' => 'default',
            'mpp_pusat'   => 'pusat',
            'mpp_layanan' => 'layanan',
        ];

        foreach ($connections as $database => $group) {
            try {
                $db = Database::connect($group);

                $db->initialize();

                $result = $db->query('SELECT DATABASE() AS db_name')->getRow();

                CLI::write(
                    "[OK] {$database} → {$result->db_name}",
                    'green'
                );
            } catch (\Throwable $e) {
                CLI::write(
                    "[GAGAL] {$database} → {$e->getMessage()}",
                    'red'
                );
            }
        }
    }
}