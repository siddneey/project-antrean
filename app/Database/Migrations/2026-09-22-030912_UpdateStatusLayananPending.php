<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateStatusLayananPending extends Migration
{
    protected $DBGroup = 'transaksi';

    public function up()
    {
        /*
        |--------------------------------------------------------------------------
        | Migrasikan data lama
        |--------------------------------------------------------------------------
        */

        $this->db->query("
            UPDATE riwayat_layanan
            SET status_layanan = 'PENDING'
            WHERE status_layanan = 'DILEWATI'
        ");

        /*
        |--------------------------------------------------------------------------
        | Ubah ENUM status layanan
        |--------------------------------------------------------------------------
        */

        $this->db->query("
            ALTER TABLE riwayat_layanan
            MODIFY status_layanan
            ENUM(
                'MENUNGGU',
                'DIPANGGIL',
                'DILAYANI',
                'PENDING',
                'SELESAI'
            )
            NOT NULL
            DEFAULT 'MENUNGGU'
        ");
    }

    public function down()
    {
        /*
        |--------------------------------------------------------------------------
        | Kembalikan PENDING menjadi DILEWATI
        |--------------------------------------------------------------------------
        */

        $this->db->query("
            UPDATE riwayat_layanan
            SET status_layanan = 'DILEWATI'
            WHERE status_layanan = 'PENDING'
        ");

        /*
        |--------------------------------------------------------------------------
        | Kembalikan ENUM lama
        |--------------------------------------------------------------------------
        */

        $this->db->query("
            ALTER TABLE riwayat_layanan
            MODIFY status_layanan
            ENUM(
                'MENUNGGU',
                'DIPANGGIL',
                'DILAYANI',
                'DILEWATI',
                'SELESAI'
            )
            NOT NULL
            DEFAULT 'MENUNGGU'
        ");
    }
}