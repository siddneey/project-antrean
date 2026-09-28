<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
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

            'role_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],

            'instansi_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
            ],

            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],

            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],

            'password' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
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

        $this->forge->addUniqueKey('username');

        $this->forge->addForeignKey(
            'role_id',
            'roles',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->forge->addForeignKey(
            'instansi_id',
            'instansi',
            'id',
            'SET NULL',
            'RESTRICT'
        );

        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users', true);
    }
}