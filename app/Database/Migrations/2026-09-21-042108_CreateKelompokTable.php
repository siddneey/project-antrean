<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKelompokTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],

            'nama_kelompok' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
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
        $this->forge->addUniqueKey('nama_kelompok');

        $this->forge->createTable('kelompok');
    }

    public function down()
    {
        $this->forge->dropTable('kelompok', true);
    }
}