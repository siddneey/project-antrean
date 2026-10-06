<?php

namespace App\Services;

use Config\Database;
use RuntimeException;

class SubdisplayService
{
    protected $dbAntrean;
    protected $dbLayanan;
    protected $dbPusat;

    public function __construct()
    {
        $this->dbAntrean = Database::connect('default');
        $this->dbLayanan = Database::connect('layanan');
        $this->dbPusat   = Database::connect('pusat');
    }

    /**
     * Mengambil data subdisplay berdasarkan grup.
     *
     * Subdisplay bersifat visual.
     * Tidak ada data audio di response.
     */
    public function getSubdisplayByGrup(int $grupId): array
    {
        if ($grupId < 1) {
            throw new RuntimeException('ID grup tidak valid.');
        }

        /*
         * ---------------------------------------------------------------------
         * Validasi grup
         * ---------------------------------------------------------------------
         */

        $grup = $this->dbPusat
            ->table('grup g')
            ->select(
                'g.id,
                 g.kelompok_id,
                 g.nama_grup'
            )
            ->where('g.id', $grupId)
            ->get()
            ->getRowArray();

        if (!$grup) {
            throw new RuntimeException('Grup tidak ditemukan.');
        }

        /*
         * ---------------------------------------------------------------------
         * Ambil seluruh instansi dalam grup
         * ---------------------------------------------------------------------
         */

        $instansi = $this->dbPusat
            ->table('instansi i')
            ->select(
                'i.id,
                 i.grup_id,
                 i.nama_instansi,
                 i.logo'
            )
            ->where('i.grup_id', $grupId)
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResultArray();

        /*
         * Jika grup tidak memiliki instansi,
         * tetap kembalikan struktur response yang konsisten.
         */

        if (empty($instansi)) {
            return [
                'grup' => [
                    'id'          => (int) $grup['id'],
                    'kelompok_id' => (int) $grup['kelompok_id'],
                    'nama_grup'   => $grup['nama_grup'],
                ],
                'instansi' => [],
            ];
        }

        $instansiIds = array_map(
            static fn ($row) => (int) $row['id'],
            $instansi
        );

        /*
         * ---------------------------------------------------------------------
         * Ambil panggilan PANGGIL terbaru untuk instansi dalam grup
         * ---------------------------------------------------------------------
         *
         * Hanya panggilan yang terkait antrean HARI INI yang dipakai.
         *
         * Ini mencegah panggilan dari hari sebelumnya muncul
         * sebagai latest_call ketika hari sudah berganti.
         */

        $panggilan = $this->dbLayanan
            ->table('riwayat_panggilan rp')
            ->select(
                'rp.id AS riwayat_panggilan_id,
                 rp.riwayat_layanan_id,
                 rp.petugas_id,
                 rp.aksi,
                 rp.waktu,
                 rp.keterangan,
                 rl.antrean_id,
                 rl.instansi_id,
                 rl.layanan_id,
                 rl.status_layanan'
            )
            ->join(
                'riwayat_layanan rl',
                'rl.id = rp.riwayat_layanan_id',
                'inner'
            )
            ->where('rp.aksi', 'PANGGIL')
            ->whereIn('rl.instansi_id', $instansiIds)
            ->orderBy('rp.id', 'DESC')
            ->get()
            ->getResultArray();

        /*
         * ---------------------------------------------------------------------
         * Ambil antrean yang relevan
         * ---------------------------------------------------------------------
         *
         * Daripada langsung mempercayai panggilan terbaru, kita validasi
         * antrean tersebut melalui DB antrean dan hanya menerima antrean
         * dengan tanggal_antrean hari ini.
         */

        $antreanIds = [];

        foreach ($panggilan as $row) {
          
        $antreanIds[] = (int) $row['antrean_id'];
        }

        $antreanIds = array_values(
            array_unique($antreanIds)
        );

        $antreanMap = [];

        if (!empty($antreanIds)) {
            $antreanRows = $this->dbAntrean
                ->table('antrean')
                ->select(
                    'id,
                     tanggal_antrean,
                     nomor_antrean,
                     jenis_antrean,
                     instansi_awal_id,
                     waktu_ambil'
                )
                ->whereIn('id', $antreanIds)
                ->where(
                    'tanggal_antrean',
                    date('Y-m-d')
                )
                ->get()
                ->getResultArray();

            foreach ($antreanRows as $row) {
                $antreanMap[(int) $row['id']] = $row;
            }
        }

        /*
         * ---------------------------------------------------------------------
         * Tentukan panggilan terbaru untuk masing-masing instansi
         * ---------------------------------------------------------------------
         *
         * Karena $panggilan sudah diurutkan DESC berdasarkan ID,
         * panggilan pertama yang ditemukan untuk suatu instansi
         * adalah panggilan terbaru.
         *
         * Hanya panggilan yang memiliki antrean hari ini yang dimasukkan.
         */

        $latestByInstansi = [];

        foreach ($panggilan as $row) {
            $instansiId = (int) $row['instansi_id'];
            $antreanId  = (int) $row['antrean_id'];

            if (!isset($antreanMap[$antreanId])) {
                continue;
            }

            if (!isset($latestByInstansi[$instansiId])) {
                $latestByInstansi[$instansiId] = $row;
            }
        }

        /*
         * ---------------------------------------------------------------------
         * Gabungkan data instansi + latest call
         * ---------------------------------------------------------------------
         */

        $result = [];

        foreach ($instansi as $item) {
            $instansiId = (int) $item['id'];

            $dataInstansi = [
                'id'            => $instansiId,
                'grup_id'       => (int) $item['grup_id'],
                'nama_instansi' => $item['nama_instansi'],
                'logo'          => $item['logo'],
                'latest_call'   => null,
            ];

            if (isset($latestByInstansi[$instansiId])) {
                $call = $latestByInstansi[$instansiId];

                $antreanId = (int) $call['antrean_id'];
                $antrean   = $antreanMap[$antreanId] ?? null;

                if ($antrean !== null) {
                    $dataInstansi['latest_call'] = [
                        'riwayat_panggilan_id'
                            => (int) $call['riwayat_panggilan_id'],

                        'riwayat_layanan_id'
                            => (int) $call['riwayat_layanan_id'],

                        'antrean_id'
                            => $antreanId,

                        'nomor_antrean'
                            => (int) $antrean['nomor_antrean'],

                        'jenis_antrean'
                            => $antrean['jenis_antrean'],

                        'tanggal_antrean'
                            => $antrean['tanggal_antrean'],

                        'instansi_id'
                            => $instansiId,

                        'status_layanan'
                            => $call['status_layanan'],

                        'petugas_id'
                            => $call['petugas_id'] !== null
                                ? (int) $call['petugas_id']
                                : null,

                        'aksi'
                            => $call['aksi'],

                        'waktu'
                            => $call['waktu'],

                        'keterangan'
                            => $call['keterangan'],
                    ];
                }
            }

            $result[] = $dataInstansi;
        }

        /*
         * ---------------------------------------------------------------------
         * Response
         * ---------------------------------------------------------------------
         */

        return [
            'grup' => [
                'id'          => (int) $grup['id'],
                'kelompok_id' => (int) $grup['kelompok_id'],
                'nama_grup'   => $grup['nama_grup'],
            ],
            'instansi' => $result,
        ];
    }
}