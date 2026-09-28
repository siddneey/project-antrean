<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();

        $instansi = $db->table('instansi');

        $layanan = [
            'SAMSAT' => [
                'Perpanjangan STNK',
            ],

            'BPJS KESEHATAN' => [
                'Layanan BPJS Kesehatan',
            ],

            'DISNAKER' => [
                'Pembuatan Kartu Kuning (AK-1)',
                'Informasi dan Konsultasi Lowongan Pekerjaan',
                'Informasi dan Konsultasi Perekrutan Karyawan',
                'Permohonan Tanda Daftar BKK',
                'Pengajuan Rekom ID CPMI',
                'Pendaftaran Pelatihan Kewirausahaan',
                'Pendaftaran Pelatihan BLK',
            ],

            'ATR BPN' => [
                'Layanan Peningkatan Hak',
                'Roya BPN KKPR',
                'Izin Berusaha BPN',
            ],

            'BPOM' => [
                'Layanan BPOM',
            ],

            'DINAS LINGKUNGAN HIDUP' => [
                'Penilaian AMDAL',
                'Penilaian UKL UPL',
            ],

            'PTSP 1' => [
                'Layanan PTSP 1',
            ],

            'PTSP 2' => [
                'Layanan PTSP 2',
            ],

            'BANK BANTEN' => [
                'Layanan Bank Banten',
            ],

            'BANK BJB' => [
                'Layanan Bank BJB',
            ],

            'Baznas' => [
                'Testing Baznas',
                'Dua',
                'Tiga',
            ],

            'BAPENDA' => [
                'Pendaftaran SPPT PBB-P2 Objek Pajak Baru',
                'Permohonan SPPT-PBB-P2 Mutasi Sebagian/Seluruhnya Objek dan Subjek',
                'Permohonan Surat Keterangan NJOP',
                'Permohonan Salinan SPPT PBB-P2',
                'Permohonan Keterangan Lunas PBB-P2',
                'Permohonan Penundaan Jatuh Tempo Pembayaran PBB-P2',
                'Permohonan Pengurangan PBB-2',
                'Permohonan Keberatan',
                'Permohonan Pengembalian',
                'Permohonan Pembatalan SPPT PBB-P2',
                'Permohonan Pembetulan SPPT SPPT PBB-P2',
                'Permohonan Pengurangan Sanksi Administrasi SPPT PBB-P2',
                'Permohonan Pembayaran Angsuran Pajak',
                'Permohonan Kompensasi',
                'Permohonan Aktivasi NOPD',
                'Pelayanan Permohonan penelitian/Validasi SSPD BPHTB',
                'Pelayanan Permohonan Pengurangan BPHTB',
                'Pelayanan Permohonan Resitusi/Pengembalian Pembayaran BPHTB',
            ],

            'BPKD' => [
                'Informasi dan Konsultasi Pajak Reklame',
                'Informasi dan Konsultasi Pajak Air Tanah',
                'Informasi dan Konsultasi Pajak Barang dan Jasa Tertentu (PBJT) – Restoran',
                'Informasi dan Konsultasi Pajak Penerangan jalan umum',
                'Informasi dan Konsultasi Pajak Hotel',
                'Informasi dan Konsultasi Pajak Parkir',
                'Informasi dan Konsultasi Pajak Hiburan (reflexiologi,karaoke,mandi uap, spa, permainan ketangkasan, permainan anak2,dll)',
            ],

            'BPJS TK' => [
                'Layanan BPJS Tenaga Kerja',
            ],

            'KEJARI' => [
                'E-Tilang & Konsultasi Hukum',
            ],

            'DUKCAPIL' => [
                'Akta Kelahiran',
                'Akta Kematian',
                'Kartu Keluarga',
                'Pindah/Datang',
                'Cetak KTP',
                'Perekaman E-KTP',
                'Kartu Identitas Anak (KIA)',
            ],

            'DITJEN AHU' => [
                'Informasi AHU',
                'Konsultasi AHU',
            ],

            'PENGADILAN AGAMA' => [
                'Layanan Informasi P. Agama',
                'Layanan Pengaduan P. Agama',
                'Pendaftaran E-Court P. Agama',
                'Pengambilan Produk Pengadilan P. Agama',
            ],

            'PELAYANAN PBG' => [
                'Layanan PBG',
            ],

            'DPUPR' => [
                'Informasi Umum Tata Ruang',
                'Informasi Pengesahan Rencana Tapak',
                'Informasi KKPR Non-berusaha',
                'Informasi KKPR Berusaha OSS',
                'Informasi Surat Keterangan Peruntukan Lahan',
                'Informasi Surat Rekomendasi Teknis Peil Banjir',
                'Informasi Surat Rekomendasi Teknis Utilitas',
                'Informasi Surat Rekomendasi Penurunan Trotoar / Pembongkaran Trotoar',
                'Informasi Surat Rekomendasi Pembangunan Jembatan',
                'Infomasi terkait Pemeliharaan Jalan',
                'Informasi terkait Pemeliharaan Drainase',
                'Informasi pengujian kelayakan jalan',
            ],

            'PERKIM' => [
                'Pelayanan Informasi PBG dan SLF',
                'Pelayanan Informasi Air Minum Air Limbah',
            ],

            'DISHUB' => [
                'Layanan DISHUB',
            ],

            'BNN' => [
                'Pelayanan BNN',
            ],

            'POLRES' => [
                'Perpanjangan SIM',
                'Pembuatan SIM Baru',
                'Layanan SKCK',
            ],

            'PT. POS INDONESIA' => [
                'POS',
            ],

            'TASPEN' => [
                'Pelayanan TASPEN',
            ],

            'PLN' => [
                'Layanan Pasang Baru dan Tambah Daya',
            ],

            'PDAM' => [
                'Pendaftaran PDAM',
            ],
        ];

        foreach ($layanan as $namaInstansi => $daftarLayanan) {
            $dataInstansi = $instansi
                ->where('nama_instansi', $namaInstansi)
                ->get()
                ->getRow();

            if (!$dataInstansi) {
                throw new \RuntimeException(
                    "Instansi '{$namaInstansi}' tidak ditemukan."
                );
            }

            foreach ($daftarLayanan as $namaLayanan) {
                $db->table('layanan')->insert([
                    'instansi_id' => $dataInstansi->id,
                    'nama_layanan' => $namaLayanan,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
}