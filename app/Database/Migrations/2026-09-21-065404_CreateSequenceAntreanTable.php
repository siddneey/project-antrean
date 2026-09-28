<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSequenceAntreanTable extends Migration
{
    protected $DBGroup = 'transaksi';

    public function up()
    {
        $this->forge->addField([
            'tanggal' => [
                'type' => 'DATE',
            ],
            'nomor_terakhir' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'default' => 0,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('tanggal', true);

        $this->forge->createTable('sequence_antrean');
    }

    public function down()
    {
        $this->forge->dropTable('sequence_antrean', true);
    }
}