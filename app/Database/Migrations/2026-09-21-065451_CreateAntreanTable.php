<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAntreanTable extends Migration
{
    protected $DBGroup = 'transaksi';

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'tanggal_antrean' => [
                'type' => 'DATE',
            ],
            'nomor_antrean' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'jenis_antrean' => [
                'type' => 'ENUM',
                'constraint' => ['BIASA', 'PRIORITAS'],
            ],
            'instansi_awal_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'waktu_ambil' => [
                'type' => 'DATETIME',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->addUniqueKey([
            'tanggal_antrean',
            'nomor_antrean',
        ]);

        $this->forge->addKey('instansi_awal_id');

        $this->forge->createTable('antrean');
    }

    public function down()
    {
        $this->forge->dropTable('antrean', true);
    }
}