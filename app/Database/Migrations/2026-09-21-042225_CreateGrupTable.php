<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGrupTable extends Migration
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

            'kelompok_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'nama_grup' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
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
            'kelompok_id',
            'kelompok',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->createTable('grup');
    }

    public function down()
    {
        $this->forge->dropTable('grup', true);
    }
}