<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateInstansiTable extends Migration
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

            'grup_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'nama_instansi' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],

            'logo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
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
            'grup_id',
            'grup',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->createTable('instansi');
    }

    public function down()
    {
        $this->forge->dropTable('instansi', true);
    }
}