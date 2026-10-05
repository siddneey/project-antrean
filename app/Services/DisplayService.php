<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class DisplayService
{
    protected BaseConnection $dbAntrean;
    protected BaseConnection $dbLayanan;
    protected BaseConnection $dbPusat;

    public function __construct()
    {
        $this->dbAntrean = db_connect('default');
        $this->dbLayanan = db_connect('layanan');
        $this->dbPusat   = db_connect('pusat');
    }

    /**
     * Mengambil panggilan terbaru berdasarkan kelompok.
     *
     * Kelompok digunakan sebagai zona display/audio.
     *
     * Flow:
     * riwayat_panggilan
     *      ↓
     * riwayat_layanan
     *      ↓
     * antrean
     *      ↓
     * instansi
     *      ↓
     * grup
     *      ↓
     * kelompok
     */
    public function getPanggilanTerbaruByKelompok(
        int $kelompokId
    ): ?array {
        if ($kelompokId < 1) {
            throw new RuntimeException(
                'Kelompok tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil daftar instansi dalam kelompok
        |--------------------------------------------------------------------------
        */

        $instansiRows = $this->dbPusat
            ->table('instansi')
            ->select([
                'instansi.id',
                'instansi.nama_instansi',
                'instansi.grup_id',
                'grup.kelompok_id',
            ])
            ->join(
                'grup',
                'grup.id = instansi.grup_id',
                'inner'
            )
            ->where(
                'grup.kelompok_id',
                $kelompokId
            )
            ->get()
            ->getResultArray();

        if (empty($instansiRows)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Mapping instansi
        |--------------------------------------------------------------------------
        */

        $instansiMap = [];

        foreach ($instansiRows as $instansi) {
            $instansiMap[
                (int) $instansi['id']
            ] = $instansi;
        }

        $instansiIds = array_keys($instansiMap);

        if (empty($instansiIds)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil panggilan terbaru
        |--------------------------------------------------------------------------
        |
        | Panggilan yang dipakai display adalah aksi PANGGIL.
        |
        | Karena satu antrean dapat dipanggil berkali-kali,
        | order berdasarkan id DESC akan mengambil panggilan
        | paling baru.
        |
        */

        $panggilanRows = $this->dbLayanan
            ->table('riwayat_panggilan rp')
            ->select([
                'rp.id AS riwayat_panggilan_id',
                'rp.riwayat_layanan_id',
                'rp.petugas_id',
                'rp.aksi',
                'rp.waktu',
                'rp.keterangan',

                'rl.antrean_id',
                'rl.instansi_id',
                'rl.status_layanan',
            ])
            ->join(
                'riwayat_layanan rl',
                'rl.id = rp.riwayat_layanan_id',
                'inner'
            )
            ->where(
                'rp.aksi',
                'PANGGIL'
            )
            ->whereIn(
                'rl.instansi_id',
                $instansiIds
            )
            ->orderBy(
                'rp.id',
                'DESC'
            )
            ->limit(100)
            ->get()
            ->getResultArray();

        if (empty($panggilanRows)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Cari panggilan pertama yang benar-benar merupakan antrean hari ini
        |--------------------------------------------------------------------------
        */

        foreach ($panggilanRows as $panggilan) {

            $antrean = $this->dbAntrean
                ->table('antrean')
                ->select([
                    'id',
                    'nomor_antrean',
                    'tanggal_antrean',
                    'jenis_antrean',
                ])
                ->where(
                    'id',
                    $panggilan['antrean_id']
                )
                ->where(
                    'tanggal_antrean',
                    date('Y-m-d')
                )
                ->get()
                ->getRowArray();

            if (empty($antrean)) {
                continue;
            }

            $instansiId = (int) $panggilan['instansi_id'];

            if (!isset($instansiMap[$instansiId])) {
                continue;
            }

            $instansi = $instansiMap[$instansiId];

            /*
            |--------------------------------------------------------------------------
            | Data siap dikirim ke Display
            |--------------------------------------------------------------------------
            */

            return [
                'riwayat_panggilan_id' =>
                    (int) $panggilan['riwayat_panggilan_id'],

                'riwayat_layanan_id' =>
                    (int) $panggilan['riwayat_layanan_id'],

                'antrean_id' =>
                    (int) $panggilan['antrean_id'],

                'nomor_antrean' =>
                    (int) $antrean['nomor_antrean'],

                'jenis_antrean' =>
                    $antrean['jenis_antrean'],

                'tanggal_antrean' =>
                    $antrean['tanggal_antrean'],

                'instansi_id' =>
                    $instansiId,

                'nama_instansi' =>
                    $instansi['nama_instansi'],

                'grup_id' =>
                    (int) $instansi['grup_id'],

                'kelompok_id' =>
                    (int) $instansi['kelompok_id'],

                'petugas_id' =>
                    (int) $panggilan['petugas_id'],

                'status_layanan' =>
                    $panggilan['status_layanan'],

                'aksi' =>
                    $panggilan['aksi'],

                'waktu' =>
                    $panggilan['waktu'],

                'keterangan' =>
                    $panggilan['keterangan'],

                /*
                |--------------------------------------------------------------------------
                | Informasi audio
                |--------------------------------------------------------------------------
                |
                | Frontend Display yang akan menyusun audio:
                |
                | "Nomor antrean"
                | + nomor
                | + "di loket"
                | + nama instansi
                |
                */

                'audio' => [
                    'enabled' => true,

                    'nomor_antrean' =>
                        (int) $antrean['nomor_antrean'],

                    'nama_instansi' =>
                        $instansi['nama_instansi'],

                    'kelompok_id' =>
                        (int) $instansi['kelompok_id'],
                ],
            ];
        }

        return null;
    }

    /**
     * Mengambil data display berdasarkan kelompok.
     *
     * Endpoint Display nantinya dapat memakai method ini
     * untuk mendapatkan informasi kelompok beserta
     * panggilan terbaru.
     */
    public function getDisplayByKelompok(
        int $kelompokId
    ): array {
        if ($kelompokId < 1) {
            throw new RuntimeException(
                'Kelompok tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi kelompok
        |--------------------------------------------------------------------------
        */

        $kelompok = $this->dbPusat
            ->table('kelompok')
            ->select([
                'id',
                'nama_kelompok',
            ])
            ->where(
                'id',
                $kelompokId
            )
            ->get()
            ->getRowArray();

        if (!$kelompok) {
            throw new RuntimeException(
                'Kelompok tidak ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Daftar grup dalam kelompok
        |--------------------------------------------------------------------------
        */

        $grup = $this->dbPusat
            ->table('grup')
            ->select([
                'id',
                'nama_grup',
                'kelompok_id',
            ])
            ->where(
                'kelompok_id',
                $kelompokId
            )
            ->orderBy(
                'id',
                'ASC'
            )
            ->get()
            ->getResultArray();

        /*
        |--------------------------------------------------------------------------
        | Panggilan terbaru
        |--------------------------------------------------------------------------
        */

        $panggilanTerbaru =
            $this->getPanggilanTerbaruByKelompok(
                $kelompokId
            );

        return [
            'kelompok' => [
                'id' =>
                    (int) $kelompok['id'],

                'nama_kelompok' =>
                    $kelompok['nama_kelompok'],
            ],

            'grup' => $grup,

            'panggilan_terbaru' =>
                $panggilanTerbaru,
        ];
    }

    /**
     * Mengecek apakah panggilan tertentu masih merupakan
     * panggilan terbaru pada kelompok.
     *
     * Berguna nanti agar frontend tidak memutar audio
     * yang sama berulang-ulang ketika melakukan polling.
     */
    public function isPanggilanTerbaru(
        int $kelompokId,
        int $riwayatPanggilanId
    ): bool {
        $terbaru =
            $this->getPanggilanTerbaruByKelompok(
                $kelompokId
            );

        if (!$terbaru) {
            return false;
        }

        return
            (int) $terbaru['riwayat_panggilan_id']
            === $riwayatPanggilanId;
    }
}