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

        // Pastikan grup ada.
        $grup = $this->dbPusat
            ->table('grup g')
            ->select('g.id, g.kelompok_id, g.nama_grup')
            ->where('g.id', $grupId)
            ->get()
            ->getRowArray();

        if (!$grup) {
            throw new RuntimeException('Grup tidak ditemukan.');
        }

        // Ambil seluruh instansi dalam grup.
        $instansi = $this->dbPusat
            ->table('instansi i')
            ->select(
                'i.id, i.grup_id, i.nama_instansi, i.logo'
            )
            ->where('i.grup_id', $grupId)
            ->orderBy('i.id', 'ASC')
            ->get()
            ->getResultArray();

        if (empty($instansi)) {
            return [
                'grup'     => $grup,
                'instansi' => [],
            ];
        }

        $instansiIds = array_map(
            static fn ($row) => (int) $row['id'],
            $instansi
        );

        /*
         * Ambil panggilan terbaru untuk masing-masing instansi.
         *
         * Kita tidak mengambil satu panggilan global saja,
         * karena satu grup dapat memiliki beberapa instansi.
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
                'rl.id = rp.riwayat_layanan_id'
            )
            ->where('rp.aksi', 'PANGGIL')
            ->whereIn('rl.instansi_id', $instansiIds)
            ->orderBy('rp.id', 'DESC')
            ->get()
            ->getResultArray();

        /*
         * Index panggilan berdasarkan instansi.
         * Satu instansi hanya ditampilkan panggilan terakhirnya.
         */
        $latestByInstansi = [];

        foreach ($panggilan as $row) {
            $instansiId = (int) $row['instansi_id'];

            if (!isset($latestByInstansi[$instansiId])) {
                $latestByInstansi[$instansiId] = $row;
            }
        }

        /*
         * Ambil nomor antrean dari DB antrean.
         */
        $antreanIds = [];

        foreach ($latestByInstansi as $row) {
            $antreanIds[] = (int) $row['antrean_id'];
        }

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
                ->get()
                ->getResultArray();

            foreach ($antreanRows as $row) {
                $antreanMap[(int) $row['id']] = $row;
            }
        }

        /*
         * Gabungkan data instansi + panggilan + antrean.
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

                if ($antrean) {
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

        return [
            'grup'     => [
                'id'           => (int) $grup['id'],
                'kelompok_id'  => (int) $grup['kelompok_id'],
                'nama_grup'    => $grup['nama_grup'],
            ],
            'instansi' => $result,
        ];
    }
}