<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLayananTable extends Migration
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

            'instansi_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'nama_layanan' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
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

        $this->forge->addForeignKey(
            'instansi_id',
            'instansi',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->createTable('layanan');
    }

    public function down()
    {
        $this->forge->dropTable('layanan', true);
    }
}