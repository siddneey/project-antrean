<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRiwayatLayananTable extends Migration
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
            'antrean_id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
            ],
            'instansi_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'layanan_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'petugas_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'status_layanan' => [
                'type' => 'ENUM',
                'constraint' => [
                    'MENUNGGU',
                    'DIPANGGIL',
                    'DILEWATI',
                    'DILAYANI',
                    'SELESAI',
                ],
                'default' => 'MENUNGGU',
            ],
            'waktu_masuk' => [
                'type' => 'DATETIME',
            ],
            'waktu_mulai' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'waktu_selesai' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'keterangan' => [
                'type' => 'TEXT',
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
            'antrean_id',
            'antrean',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->forge->addKey('instansi_id');
        $this->forge->addKey('layanan_id');
        $this->forge->addKey('petugas_id');
        $this->forge->addKey('status_layanan');

        $this->forge->createTable('riwayat_layanan');
    }

    public function down()
    {
        $this->forge->dropTable('riwayat_layanan', true);
    }
}