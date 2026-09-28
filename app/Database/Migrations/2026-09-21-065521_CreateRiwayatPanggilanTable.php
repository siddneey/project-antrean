<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRiwayatPanggilanTable extends Migration
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
            'riwayat_layanan_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'petugas_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'aksi' => [
                'type' => 'ENUM',
                'constraint' => ['PANGGIL', 'SKIP'],
            ],
            'waktu' => [
                'type' => 'DATETIME',
            ],
            'keterangan' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
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
            'riwayat_layanan_id',
            'riwayat_layanan',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addKey('petugas_id');
        $this->forge->addKey('aksi');

        $this->forge->createTable('riwayat_panggilan');
    }

    public function down()
    {
        $this->forge->dropTable('riwayat_panggilan', true);
    }
}