<?php

namespace App\Services;

use App\Models\AntreanModel;
use App\Models\SequenceAntreanModel;
use App\Models\RiwayatLayananModel;
use App\Models\RiwayatPanggilanModel;
use App\Models\KuotaInstansiModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class AntreanService
{
    private const KUOTA_BIASA_DEFAULT = 50;
    private const KUOTA_PRIORITAS_DEFAULT = 30;

    protected BaseConnection $dbAntrean;
    protected BaseConnection $dbLayanan;
    protected BaseConnection $dbPusat;

    protected AntreanModel $antreanModel;
    protected SequenceAntreanModel $sequenceModel;
    protected RiwayatLayananModel $riwayatLayananModel;
    protected RiwayatPanggilanModel $riwayatPanggilanModel;
    protected KuotaInstansiModel $kuotaInstansiModel;


    /*
    |--------------------------------------------------------------------------
    | Jam pelayanan
    |--------------------------------------------------------------------------
    */

    protected string $jamMulaiAmbil = '08:00:00';
    protected string $jamSelesaiAmbil = '15:00:00';

    protected string $jamMulaiPelayanan = '08:00:00';
    protected string $jamSelesaiPelayanan = '16:00:00';

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct()
    {
        $this->dbAntrean = db_connect('default');
        $this->dbLayanan = db_connect('layanan');
        $this->dbPusat = db_connect('pusat');

        $this->antreanModel = new AntreanModel();
        $this->sequenceModel = new SequenceAntreanModel();
        $this->riwayatLayananModel = new RiwayatLayananModel();
        $this->riwayatPanggilanModel = new RiwayatPanggilanModel();
        $this->kuotaInstansiModel = new KuotaInstansiModel();
    }

    /*
    |--------------------------------------------------------------------------
    | AMBIL ANTREAN
    |--------------------------------------------------------------------------
    |
    | Digunakan masyarakat untuk mengambil antrean.
    |
    | Jenis:
    | - BIASA
    | - PRIORITAS
    |
    | Tanggal:
    | - hari ini
    | - maksimal 7 hari ke depan
    |
    */

    public function ambilAntrean(
        int $instansiId,
        string $jenisAntrean,
        string $tanggal
    ): array {
        $jenisAntrean = strtoupper(trim($jenisAntrean));
        $tanggalHariIni = date('Y-m-d');
        $waktuAmbil = date('Y-m-d H:i:s');

        /*
        |--------------------------------------------------------------------------
        | Validasi
        |--------------------------------------------------------------------------
        */

        $this->validasiJenisAntrean($jenisAntrean);
        $this->validasiHariPelayanan($tanggal);
        $this->validasiJamPengambilan($tanggal);
        $instansi = $this->validasiInstansi($instansiId);

        /*
        |--------------------------------------------------------------------------
        | Simpan antrean ke DATABASE ANTREAN
        |--------------------------------------------------------------------------
        */

        $this->dbAntrean->transBegin();

        try {
            /*
            |--------------------------------------------------------------------------
            | Cek kuota instansi
            |--------------------------------------------------------------------------
            */

            $kuota = $this->getKuotaInstansiByJenis(
                $instansiId,
                $jenisAntrean,
                $tanggal
            );

            $jumlahAntrean = $this->getJumlahAntreanInstansi(
                $instansiId,
                $jenisAntrean,
                $tanggal
            );

            if ($jumlahAntrean >= $kuota) {
                $this->dbAntrean->transRollback();

                return [
                    'status' => false,
                    'message' => sprintf(
                        'Kuota antrean %s untuk tanggal tersebut sudah habis.',
                        $jenisAntrean
                    ),
                    'data' => [
                        'tanggal' => $tanggal,
                        'jenis_antrean' => $jenisAntrean,
                        'kuota' => $kuota,
                        'terpakai' => $jumlahAntrean,
                        'tersisa' => 0,
                    ],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Generate nomor antrean
            |--------------------------------------------------------------------------
            */

            $nomorAntrean = $this->generateNomorAntreanDalamTransaksi(
                $tanggal
            );

            /*
            |--------------------------------------------------------------------------
            | Simpan data antrean
            |--------------------------------------------------------------------------
            */

            $antreanId = $this->antreanModel->insert([
                'tanggal_antrean'  => $tanggal,
                'nomor_antrean'    => $nomorAntrean,
                'jenis_antrean'    => $jenisAntrean,
                'instansi_awal_id' => $instansiId,
                'waktu_ambil'      => $waktuAmbil,
            ], true);

            if (!$antreanId) {
                throw new RuntimeException(
                    'Gagal membuat data antrean.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan transaksi DATABASE ANTREAN berhasil
            |--------------------------------------------------------------------------
            */

            if ($this->dbAntrean->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal menyimpan data antrean.'
                );
            }

        $this->dbAntrean->transCommit();

        } catch (\Throwable $e) {

        $this->dbAntrean->transRollback();

        throw $e;
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan riwayat layanan ke DATABASE LAYANAN
        |--------------------------------------------------------------------------
        */

        try {
            $riwayatLayananId = $this->riwayatLayananModel->insert([
                'antrean_id'     => $antreanId,
                'instansi_id'    => $instansiId,
                'layanan_id'     => null,
                'petugas_id'     => null,
                'status_layanan' => 'MENUNGGU',
                'waktu_masuk'    => $waktuAmbil,
                'waktu_mulai'    => null,
                'waktu_selesai'  => null,
                'keterangan'     => $tanggal === $tanggalHariIni
                    ? 'Antrean hari ini'
                    : 'Booking antrean hari selanjutnya',
            ], true);

            if (!$riwayatLayananId) {
                throw new RuntimeException(
                    'Gagal membuat riwayat layanan.'
                );
            }

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | COMPENSATION
            |--------------------------------------------------------------------------
            | Karena DATABASE ANTREAN dan DATABASE LAYANAN
            | merupakan koneksi berbeda, rollback transaksi
            | tidak bisa membatalkan insert pada node lain.
            |
            | Jika riwayat gagal dibuat, hapus kembali antrean
            | yang baru saja dibuat.
            |--------------------------------------------------------------------------
            */

            $this->dbAntrean
                ->table('antrean')
                ->where('id', $antreanId)
                ->delete();

            throw $e;
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'status' => true,
            'message' => $tanggal === $tanggalHariIni
                ? 'Antrean berhasil diambil.'
                : 'Booking antrean berhasil.',
            'data' => [
                'antrean_id'         => $antreanId,
                'riwayat_layanan_id' => $riwayatLayananId,
                'nomor_antrean'      => $nomorAntrean,
                'tanggal_antrean'    => $tanggal,
                'jenis_antrean'      => $jenisAntrean,
                'instansi_id'        => $instansiId,
                'instansi'           => $instansi['nama_instansi'],
                'waktu_ambil'        => $waktuAmbil,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR ANTREAN
    |--------------------------------------------------------------------------
    |
    | Harus dipanggil ketika transaction sedang aktif.
    |
    | Nomor antrean bersifat global per tanggal.
    |
    */

    protected function generateNomorAntreanDalamTransaksi(
        string $tanggal
    ): int {
        $builder = $this->dbAntrean
            ->table('sequence_antrean');

        /*
        |--------------------------------------------------------------------------
        | Buat sequence jika belum ada
        |--------------------------------------------------------------------------
        */

        $builder->ignore(true)->insert([
            'tanggal' => $tanggal,
            'nomor_terakhir' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Lock sequence
        |--------------------------------------------------------------------------
        */

        $sequence = $this->dbAntrean
            ->query(
                'SELECT tanggal, nomor_terakhir
                 FROM sequence_antrean
                 WHERE tanggal = ?
                 FOR UPDATE',
                [$tanggal]
            )
            ->getRowArray();

        if (!$sequence) {
            throw new RuntimeException(
                'Sequence antrean tidak ditemukan.'
            );
        }

        $nomorBaru = ((int) $sequence['nomor_terakhir']) + 1;

        /*
        |--------------------------------------------------------------------------
        | Update nomor terakhir
        |--------------------------------------------------------------------------
        */

        $this->dbAntrean
            ->table('sequence_antrean')
            ->where('tanggal', $tanggal)
            ->update([
                'nomor_terakhir' => $nomorBaru,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $nomorBaru;
    }

    /*
    |--------------------------------------------------------------------------
    | SELANJUTNYA / PANGGIL ANTREAN BERIKUTNYA
    |--------------------------------------------------------------------------
    |
    | Aturan:
    |
    | 1. Tidak boleh ada antrean aktif.
    | 2. Hanya mengambil status MENUNGGU.
    | 3. PRIORITAS didahulukan.
    | 4. Setelah prioritas habis, BIASA secara FIFO.
    | 5. PENDING TIDAK PERNAH dipanggil otomatis.
    |
    */

    public function panggilAntrean(
        int $petugasId,
        int $instansiId
    ): array {
        $this->validasiPetugasInstansi(
            $petugasId,
            $instansiId
        );

        $this->dbLayanan->transBegin();

        try {
            /*
            |--------------------------------------------------------------------------
            | Pastikan tidak ada antrean aktif
            |--------------------------------------------------------------------------
            */

            $layananAktif = $this->dbLayanan
                ->table('riwayat_layanan')
                ->where('instansi_id', $instansiId)
                ->whereIn(
                    'status_layanan',
                    [
                        'DIPANGGIL',
                        'DILAYANI',
                    ]
                )
                ->countAllResults();

            if ($layananAktif > 0) {
                throw new RuntimeException(
                    'Masih ada antrean yang sedang dilayani.'
                );
            }
    

            /*
            |--------------------------------------------------------------------------
            | Cari kandidat antrean MENUNGGU
            |--------------------------------------------------------------------------
            |
            | Data status dan instansi berasal dari mpp_layanan.
            | Nomor dan jenis antrean berasal dari mpp_antrean.
            |
            | PENDING sengaja tidak dimasukkan.
            |
            */

            $kandidat = $this->dbLayanan
                ->table('riwayat_layanan')
                ->select([
                    'id AS riwayat_layanan_id',
                    'antrean_id',
                    'instansi_id',
                    'layanan_id',
                    'status_layanan',
                    'waktu_masuk',
                ])
                ->where(
                    'instansi_id',
                    $instansiId
                )
                ->where(
                    'status_layanan',
                    'MENUNGGU'
                )

                /*
                ->where(
                    'DATE(waktu_masuk)',
                    date('Y-m-d'),
                    false
                )
                */

                ->orderBy(
                    'waktu_masuk',
                    'ASC'
                )
                ->orderBy(
                    'id',
                    'ASC'
                )
                ->get()
                ->getResultArray();


            log_message(
                'error',
                'SELanjutnya DEBUG | kandidat=' . json_encode($kandidat)
            );


            if (!$kandidat) {
                throw new RuntimeException(
                    'Tidak ada antrean yang dapat dipanggil.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Ambil data antrean dari mpp_antrean
            |--------------------------------------------------------------------------
            |
            | Karena database berbeda, kita tidak bisa JOIN langsung.
            | Kandidat dari mpp_layanan diperiksa satu per satu
            | terhadap data antrean.
            |
            */

            $antreanTerpilih = null;

            foreach ($kandidat as $row) {
                $dataAntrean = $this->dbAntrean
                    ->table('antrean')
                    ->select([
                        'id',
                        'nomor_antrean',
                        'tanggal_antrean',
                        'jenis_antrean',
                    ])
                    ->where(
                        'id',
                        $row['antrean_id']
                    )
                    ->where(
                        'tanggal_antrean',
                        date('Y-m-d')
                    )
                    ->get()
                    ->getRowArray();

                if (!$dataAntrean) {
                    continue;
                }

                $row['nomor_antrean'] = $dataAntrean['nomor_antrean'];
                $row['tanggal_antrean'] = $dataAntrean['tanggal_antrean'];
                $row['jenis_antrean'] = $dataAntrean['jenis_antrean'];

                /*
                |--------------------------------------------------------------------------
                | Prioritas didahulukan.
                |--------------------------------------------------------------------------
                */

                if ($antreanTerpilih === null) {
                    $antreanTerpilih = $row;
                    continue;
                }

                $prioritasSekarang =
                    $row['jenis_antrean'] === 'PRIORITAS' ? 0 : 1;

                $prioritasTerpilih =
                    $antreanTerpilih['jenis_antrean'] === 'PRIORITAS'
                        ? 0
                        : 1;

                if ($prioritasSekarang < $prioritasTerpilih) {
                    $antreanTerpilih = $row;
                }
            }

            if (!$antreanTerpilih) {
                throw new RuntimeException(
                    'Tidak ada antrean yang dapat dipanggil.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Lock riwayat layanan terpilih
            |--------------------------------------------------------------------------
            */

            $locked = $this->dbLayanan
                ->query(
                    'SELECT id, status_layanan
                    FROM riwayat_layanan
                    WHERE id = ?
                    FOR UPDATE',
                    [
                        $antreanTerpilih['riwayat_layanan_id'],
                    ]
                )
                ->getRowArray();

            if (!$locked) {
                throw new RuntimeException(
                    'Data antrean tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan masih MENUNGGU
            |--------------------------------------------------------------------------
            */

            if ($locked['status_layanan'] !== 'MENUNGGU') {
                throw new RuntimeException(
                    'Antrean sudah diproses oleh petugas lain.'
                );
            }

            $waktuPanggil = date('Y-m-d H:i:s');

            /*
            |--------------------------------------------------------------------------
            | Setelah dipanggil langsung dianggap DILAYANI
            |--------------------------------------------------------------------------
            */

            $this->dbLayanan
                ->table('riwayat_layanan')
                ->where(
                    'id',
                    $antreanTerpilih['riwayat_layanan_id']
                )
                ->update([
                    'status_layanan' => 'DILAYANI',
                    'petugas_id' => $petugasId,
                    'waktu_mulai' => $waktuPanggil,
                    'updated_at' => $waktuPanggil,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Simpan riwayat panggilan
            |--------------------------------------------------------------------------
            */

            $this->dbLayanan
                ->table('riwayat_panggilan')
                ->insert([
                    'riwayat_layanan_id' =>
                        $antreanTerpilih['riwayat_layanan_id'],

                    'petugas_id' =>
                        $petugasId,

                    'aksi' =>
                        'PANGGIL',

                    'waktu' =>
                        $waktuPanggil,

                    'keterangan' =>
                        null,

                    'created_at' =>
                        $waktuPanggil,

                    'updated_at' =>
                        $waktuPanggil,
                ]);

            if ($this->dbLayanan->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal memproses pemanggilan antrean.'
                );
            }

            $this->dbLayanan->transCommit();

            return [
                'riwayat_layanan_id' =>
                    (int) $antreanTerpilih['riwayat_layanan_id'],

                'antrean_id' =>
                    (int) $antreanTerpilih['antrean_id'],

                'nomor_antrean' =>
                    (int) $antreanTerpilih['nomor_antrean'],

                'jenis_antrean' =>
                    $antreanTerpilih['jenis_antrean'],

                'instansi_id' =>
                    (int) $antreanTerpilih['instansi_id'],

                'petugas_id' =>
                    $petugasId,

                'status_layanan' =>
                    'DILAYANI',

                'aksi' =>
                    'PANGGIL',

                'waktu' =>
                    $waktuPanggil,
            ];

        } catch (\Throwable $e) {
            $this->dbLayanan->transRollback();

            throw $e;
        }

    }

    /*
    |--------------------------------------------------------------------------
    | ULANGI PANGGILAN
    |--------------------------------------------------------------------------
    |
    | Memanggil kembali antrean yang sedang dilayani.
    |
    | Tidak membuat antrean baru.
    | Status tetap DILAYANI.
    |
    */

    public function panggilUlang(
        int $petugasId,
        int $riwayatLayananId
    ): array {
        $this->dbLayanan->transBegin();

        try {
            $riwayat = $this->getRiwayatDenganLock(
                $riwayatLayananId
            );

            if (!$riwayat) {
                throw new RuntimeException(
                    'Riwayat layanan tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan petugas yang sama
            |--------------------------------------------------------------------------
            */

            log_message(
                'error',
                'PANGGIL ULANG DEBUG | riwayat_id=' . $riwayatLayananId
                . ' | petugas_db=' . ($riwayat['petugas_id'] ?? 'NULL')
                . ' | petugas_request=' . $petugasId
                . ' | status=' . ($riwayat['status_layanan'] ?? 'NULL')
            );

            if (
                (int) $riwayat['petugas_id']
                !== $petugasId
            ) {
                throw new RuntimeException(
                    'Antrean ini bukan tanggung jawab petugas tersebut.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Hanya antrean aktif yang bisa dipanggil ulang
            |--------------------------------------------------------------------------
            */

            if (!in_array(
                $riwayat['status_layanan'],
                [
                    'DIPANGGIL',
                    'DILAYANI',
                ],
                true
            )) {
                throw new RuntimeException(
                    'Antrean tidak dapat dipanggil ulang.'
                );
            }

            $waktu = date('Y-m-d H:i:s');

            /*
            |--------------------------------------------------------------------------
            | Catat panggilan ulang
            |--------------------------------------------------------------------------
            */

            $this->dbLayanan
                ->table('riwayat_panggilan')
                ->insert([
                    'riwayat_layanan_id' =>
                        $riwayatLayananId,

                    'petugas_id' =>
                        $petugasId,

                    'aksi' =>
                        'PANGGIL',

                    'waktu' =>
                        $waktu,

                    'keterangan' =>
                        'Panggilan ulang',

                    'created_at' =>
                        $waktu,

                    'updated_at' =>
                        $waktu,
                ]);

            if ($this->dbLayanan->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal menyimpan riwayat panggilan ulang.'
                );
            }

            $this->dbLayanan->transCommit();

            return [
                'riwayat_layanan_id' =>
                    $riwayatLayananId,

                'petugas_id' =>
                    $petugasId,

                'aksi' =>
                    'PANGGIL',

                'keterangan' =>
                    'Panggilan ulang',

                'waktu' =>
                    $waktu,
            ];

        } catch (\Throwable $e) {
            $this->dbLayanan->transRollback();

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | KONFIRMASI STATUS
    |--------------------------------------------------------------------------
    |
    | Pengganti "Selesaikan Layanan".
    |
    | Status:
    | - SELESAI
    | - PENDING
    |
    | Jika SELESAI:
    | - layanan_id wajib
    | - keterangan dapat diisi
    |
    | Jika PENDING:
    | - layanan_id tidak diperlukan
    | - antrean masuk daftar pending
    |
    */

    public function konfirmasiStatus(
        int $petugasId,
        int $riwayatLayananId,
        string $status,
        ?int $layananId = null,
        ?string $keterangan = null
    ): array {
        $status = strtoupper(trim($status));

        if (!in_array(
            $status,
            [
                'SELESAI',
                'PENDING',
            ],
            true
        )) {
            throw new RuntimeException(
                'Status hanya dapat berupa SELESAI atau PENDING.'
            );
        }

        $this->dbLayanan->transBegin();

        try {
            $riwayat = $this->getRiwayatDenganLock(
                $riwayatLayananId
            );

            if (!$riwayat) {
                throw new RuntimeException(
                    'Riwayat layanan tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan petugas yang menangani
            |--------------------------------------------------------------------------
            */

            if (
                (int) $riwayat['petugas_id']
                !== $petugasId
            ) {
                throw new RuntimeException(
                    'Antrean ini bukan tanggung jawab petugas tersebut.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Hanya antrean aktif
            |--------------------------------------------------------------------------
            */

            if (!in_array(
                $riwayat['status_layanan'],
                [
                    'DIPANGGIL',
                    'DILAYANI',
                ],
                true
            )) {
                throw new RuntimeException(
                    'Antrean tidak sedang dilayani.'
                );
            }

            $waktu = date('Y-m-d H:i:s');

            /*
            |--------------------------------------------------------------------------
            | STATUS SELESAI
            |--------------------------------------------------------------------------
            */

            if ($status === 'SELESAI') {

                /*
                |--------------------------------------------------------------------------
                | Layanan wajib dipilih
                |--------------------------------------------------------------------------
                */

                if ($layananId === null) {
                    throw new RuntimeException(
                        'Detail layanan wajib dipilih ketika status SELESAI.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validasi layanan terhadap instansi
                |--------------------------------------------------------------------------
                |
                | Data master layanan berada di mpp_pusat.
                |
                */

                $this->validasiLayanan(
                    $layananId,
                    (int) $riwayat['instansi_id']
                );

                $this->dbLayanan
                    ->table('riwayat_layanan')
                    ->where(
                        'id',
                        $riwayatLayananId
                    )
                    ->update([
                        'status_layanan' => 'SELESAI',
                        'layanan_id' => $layananId,
                        'waktu_selesai' => $waktu,
                        'keterangan' => $keterangan,
                        'updated_at' => $waktu,
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | STATUS PENDING
            |--------------------------------------------------------------------------
            |
            | Pending = skip sementara.
            |
            | Tidak membutuhkan layanan_id.
            |--------------------------------------------------------------------------
            */

            if ($status === 'PENDING') {

                $this->dbLayanan
                    ->table('riwayat_layanan')
                    ->where(
                        'id',
                        $riwayatLayananId
                    )
                    ->update([
                        'status_layanan' => 'PENDING',
                        'layanan_id' => null,
                        'waktu_selesai' => null,
                        'keterangan' => $keterangan,
                        'updated_at' => $waktu,
                    ]);
            }

            if ($this->dbLayanan->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal mengubah status antrean.'
                );
            }

            $this->dbLayanan->transCommit();

            return $this->getRiwayatLayanan(
                $riwayatLayananId
            );

        } catch (\Throwable $e) {
            $this->dbLayanan->transRollback();

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PANGGIL ANTREAN PENDING
    |--------------------------------------------------------------------------
    |
    | Pending TIDAK masuk algoritma "Selanjutnya".
    |
    | Petugas memanggilnya secara manual dari tabel
    | "Antrean yang Sudah Dipanggil".
    |
    */

    public function panggilPending(
        int $petugasId,
        int $riwayatLayananId
    ): array {
        $this->dbLayanan->transBegin();

        try {
            $riwayat = $this->getRiwayatDenganLock(
                $riwayatLayananId
            );

            if (!$riwayat) {
                throw new RuntimeException(
                    'Riwayat layanan tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan petugas adalah petugas pada instansi tersebut
            |--------------------------------------------------------------------------
            */

            $this->validasiPetugasInstansi(
                $petugasId,
                (int) $riwayat['instansi_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Pastikan PENDING
            |--------------------------------------------------------------------------
            */

            if ($riwayat['status_layanan'] !== 'PENDING') {
                throw new RuntimeException(
                    'Antrean tersebut bukan antrean PENDING.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Tidak boleh ada antrean aktif lain
            |--------------------------------------------------------------------------
            */

            $layananAktif = $this->dbLayanan
                ->table('riwayat_layanan')
                ->where(
                    'instansi_id',
                    $riwayat['instansi_id']
                )
                ->whereIn(
                    'status_layanan',
                    [
                        'DIPANGGIL',
                        'DILAYANI',
                    ]
                )
                ->countAllResults();

            if ($layananAktif > 0) {
                throw new RuntimeException(
                    'Masih ada antrean yang sedang dilayani.'
                );
            }

            $waktu = date('Y-m-d H:i:s');

            /*
            |--------------------------------------------------------------------------
            | Pending kembali dilayani
            |--------------------------------------------------------------------------
            */

            $this->dbLayanan
                ->table('riwayat_layanan')
                ->where(
                    'id',
                    $riwayatLayananId
                )
                ->update([
                    'status_layanan' => 'DILAYANI',
                    'petugas_id' => $petugasId,
                    'waktu_mulai' => $waktu,
                    'waktu_selesai' => null,
                    'updated_at' => $waktu,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Catat panggilan
            |--------------------------------------------------------------------------
            */

            $this->dbLayanan
                ->table('riwayat_panggilan')
                ->insert([
                    'riwayat_layanan_id' =>
                        $riwayatLayananId,

                    'petugas_id' =>
                        $petugasId,

                    'aksi' =>
                        'PANGGIL',

                    'waktu' =>
                        $waktu,

                    'keterangan' =>
                        'Panggil antrean pending',

                    'created_at' =>
                        $waktu,

                    'updated_at' =>
                        $waktu,
                ]);

            if ($this->dbLayanan->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal memanggil antrean pending.'
                );
            }

            $this->dbLayanan->transCommit();

            return [
                'riwayat_layanan_id' =>
                    $riwayatLayananId,

                'antrean_id' =>
                    (int) $riwayat['antrean_id'],

                'nomor_antrean' =>
                    $this->getNomorAntrean(
                        (int) $riwayat['antrean_id']
                    ),

                'instansi_id' =>
                    (int) $riwayat['instansi_id'],

                'petugas_id' =>
                    $petugasId,

                'status_layanan' =>
                    'DILAYANI',

                'aksi' =>
                    'PANGGIL',

                'waktu' =>
                    $waktu,
            ];

        } catch (\Throwable $e) {
            $this->dbLayanan->transRollback();

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TERUSAN ANTREAN
    |--------------------------------------------------------------------------
    |
    | Digunakan ketika masyarakat sudah selesai ditangani di instansi
    | asal tetapi harus melanjutkan pelayanan ke instansi lain.
    |
    | Flow:
    |
    | BAPENDA
    |   ↓
    | SELESAI
    |   ↓
    | BPJS
    |   ↓
    | MENUNGGU
    |
    | Nomor antrean tetap sama.
    |
    */

    public function terusanAntrean(
        int $petugasId,
        int $riwayatLayananId,
        int $layananId,
        int $instansiTujuanId,
        ?string $keterangan = null
    ): array {
        $this->dbLayanan->transBegin();

        try {
            /*
            |--------------------------------------------------------------------------
            | Lock perjalanan layanan asal
            |--------------------------------------------------------------------------
            */

            $riwayat = $this->getRiwayatDenganLock(
                $riwayatLayananId
            );

            if (!$riwayat) {
                throw new RuntimeException(
                    'Riwayat layanan tidak ditemukan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pastikan petugas yang menangani
            |--------------------------------------------------------------------------
            */

            if (
                (int) $riwayat['petugas_id']
                !== $petugasId
            ) {
                throw new RuntimeException(
                    'Antrean ini bukan tanggung jawab petugas tersebut.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Terusan hanya bisa dilakukan ketika sedang dilayani
            |--------------------------------------------------------------------------
            */

            if (!in_array(
                $riwayat['status_layanan'],
                [
                    'DIPANGGIL',
                    'DILAYANI',
                ],
                true
            )) {
                throw new RuntimeException(
                    'Antrean tidak sedang dalam proses pelayanan.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Validasi layanan asal
            |--------------------------------------------------------------------------
            */

            $this->validasiLayanan(
                $layananId,
                (int) $riwayat['instansi_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Validasi instansi tujuan
            |--------------------------------------------------------------------------
            */

            $this->validasiInstansi(
                $instansiTujuanId
            );

            /*
            |--------------------------------------------------------------------------
            | Instansi tujuan tidak boleh sama
            |--------------------------------------------------------------------------
            */

            if (
                (int) $riwayat['instansi_id']
                === $instansiTujuanId
            ) {
                throw new RuntimeException(
                    'Instansi tujuan tidak boleh sama dengan instansi asal.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Cek perjalanan aktif di instansi tujuan
            |--------------------------------------------------------------------------
            */

            $sudahAda = $this->dbLayanan
                ->table('riwayat_layanan')
                ->where(
                    'antrean_id',
                    $riwayat['antrean_id']
                )
                ->where(
                    'instansi_id',
                    $instansiTujuanId
                )
                ->whereIn(
                    'status_layanan',
                    [
                        'MENUNGGU',
                        'DIPANGGIL',
                        'DILAYANI',
                        'PENDING',
                    ]
                )
                ->countAllResults();

            if ($sudahAda > 0) {
                throw new RuntimeException(
                    'Antrean sudah memiliki proses layanan aktif di instansi tujuan.'
                );
            }

            $waktu = date('Y-m-d H:i:s');

            /*
            |--------------------------------------------------------------------------
            | Selesaikan perjalanan di instansi asal
            |--------------------------------------------------------------------------
            */

            $this->dbLayanan
                ->table('riwayat_layanan')
                ->where(
                    'id',
                    $riwayatLayananId
                )
                ->update([
                    'status_layanan' => 'SELESAI',
                    'layanan_id' => $layananId,
                    'waktu_selesai' => $waktu,
                    'keterangan' => $keterangan,
                    'updated_at' => $waktu,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Buat perjalanan baru di instansi tujuan
            |--------------------------------------------------------------------------
            |
            | Nomor antrean TETAP sama.
            |
            | layanan_id NULL karena layanan di instansi tujuan
            | belum dipilih.
            |
            */

            $this->dbLayanan
                ->table('riwayat_layanan')
                ->insert([
                    'antrean_id' =>
                        $riwayat['antrean_id'],

                    'instansi_id' =>
                        $instansiTujuanId,

                    'layanan_id' =>
                        null,

                    'petugas_id' =>
                        null,

                    'status_layanan' =>
                        'MENUNGGU',

                    'waktu_masuk' =>
                        $waktu,

                    'waktu_mulai' =>
                        null,

                    'waktu_selesai' =>
                        null,

                    'keterangan' =>
                        null,

                    'created_at' =>
                        $waktu,

                    'updated_at' =>
                        $waktu,
                ]);

            $riwayatBaruId = $this->dbLayanan->insertID();

            if (!$riwayatBaruId) {
                throw new RuntimeException(
                    'Gagal membuat riwayat layanan tujuan.'
                );
            }

            if ($this->dbLayanan->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal memproses terusan antrean.'
                );
            }

            $this->dbLayanan->transCommit();

            return [
                'riwayat_layanan_id' =>
                    (int) $riwayatBaruId,

                'antrean_id' =>
                    (int) $riwayat['antrean_id'],

                'nomor_antrean' =>
                    $this->getNomorAntrean(
                        (int) $riwayat['antrean_id']
                    ),

                'instansi_asal_id' =>
                    (int) $riwayat['instansi_id'],

                'instansi_tujuan_id' =>
                    $instansiTujuanId,

                'layanan_asal_id' =>
                    $layananId,

                'status_asal' =>
                    'SELESAI',

                'status_tujuan' =>
                    'MENUNGGU',

                'waktu_masuk_tujuan' =>
                    $waktu,
            ];

        } catch (\Throwable $e) {
            $this->dbLayanan->transRollback();

            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET ANTREAN YANG SEDANG DILAYANI
    |--------------------------------------------------------------------------
    */

    public function getAntreanSedangDilayani(
        int $instansiId
    ): ?array {
        $dbLayanan = $this->dbLayanan;
        $dbAntrean = $this->dbAntrean;

        // Ambil riwayat yang sedang aktif dari mpp_layanan
        $riwayat = $dbLayanan
            ->table('riwayat_layanan')
            ->where(
                'instansi_id',
                $instansiId
            )
            ->whereIn(
                'status_layanan',
                [
                    'DIPANGGIL',
                    'DILAYANI',
                ]
            )
            ->orderBy(
                'id',
                'DESC'
            )
            ->limit(1)
            ->get()
            ->getRowArray();

        if (!$riwayat) {
            return null;
        }

        // Ambil data nomor antrean dari mpp_antrean
        $antrean = $dbAntrean
            ->table('antrean')
            ->select('
                nomor_antrean,
                tanggal_antrean,
                jenis_antrean
            ')
            ->where(
                'id',
                $riwayat['antrean_id']
            )
            ->where(
                'tanggal_antrean',
                date('Y-m-d')
            )
            ->get()
            ->getRowArray();

        if (!$antrean) {
            return null;
        }

        return array_merge(
            $riwayat,
            $antrean
        );
    }
    /*
    |--------------------------------------------------------------------------
    | GET ANTREAN SELANJUTNYA
    |--------------------------------------------------------------------------
    |
    | Hanya MENUNGGU.
    |
    | PENDING tidak muncul di sini.
    |
    */

    public function getAntreanSelanjutnya(
        int $instansiId
    ): array {
        $kandidat = $this->dbLayanan
            ->table('riwayat_layanan')
            ->select([
                'id AS riwayat_layanan_id',
                'antrean_id',
                'instansi_id',
                'layanan_id',
                'petugas_id',
                'status_layanan',
                'waktu_masuk',
                'waktu_mulai',
                'waktu_selesai',
                'keterangan',
                'created_at',
                'updated_at',
            ])
            ->where(
                'instansi_id',
                $instansiId
            )
            ->where(
                'status_layanan',
                'MENUNGGU'
            )
            /*
            ->where(
                'DATE(waktu_masuk)',
                date('Y-m-d'),
                false
            )
            */
            ->orderBy(
                'waktu_masuk',
                'ASC'
            )
            ->orderBy(
                'id',
                'ASC'
            )
            ->get()
            ->getResultArray();

        if (!$kandidat) {
            return [];
        }

        $hasil = [];

        foreach ($kandidat as $row) {
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
                    $row['antrean_id']
                )
                ->where(
                    'tanggal_antrean',
                    date('Y-m-d')
                )
                ->get()
                ->getRowArray();

            if (!$antrean) {
                continue;
            }

            $row['nomor_antrean'] =
                (int) $antrean['nomor_antrean'];

            $row['tanggal_antrean'] =
                $antrean['tanggal_antrean'];

            $row['jenis_antrean'] =
                $antrean['jenis_antrean'];

            $hasil[] = $row;
        }

        /*
        |--------------------------------------------------------------------------
        | PRIORITAS → BIASA → FIFO
        |--------------------------------------------------------------------------
        */

        usort(
            $hasil,
            static function (array $a, array $b): int {
                $prioritasA =
                    $a['jenis_antrean'] === 'PRIORITAS'
                        ? 0
                        : 1;

                $prioritasB =
                    $b['jenis_antrean'] === 'PRIORITAS'
                        ? 0
                        : 1;

                if ($prioritasA !== $prioritasB) {
                    return $prioritasA <=> $prioritasB;
                }

                $waktuA = strtotime($a['waktu_masuk']);
                $waktuB = strtotime($b['waktu_masuk']);

                if ($waktuA !== $waktuB) {
                    return $waktuA <=> $waktuB;
                }

                return (int) $a['riwayat_layanan_id']
                    <=> (int) $b['riwayat_layanan_id'];
            }
        );

        return $hasil;
    }

    /*
    |--------------------------------------------------------------------------
    | ALIAS LAMA
    |--------------------------------------------------------------------------
    |
    | Supaya controller lama yang masih memanggil
    | getAntreanMenunggu() tidak langsung error.
    |
    */

    public function getAntreanMenunggu(
        int $instansiId
    ): array {
        return $this->getAntreanSelanjutnya(
            $instansiId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET ANTREAN YANG SUDAH DIPANGGIL
    |--------------------------------------------------------------------------
    |
    | Menampilkan antrean yang sudah pernah dipanggil:
    |
    | - DIPANGGIL
    | - DILAYANI
    | - PENDING
    | - SELESAI
    |
    | PENDING nantinya memiliki tombol "Panggil".
    |
    */

    public function getAntreanSudahDipanggil(
        int $instansiId
    ): array {
        $dbLayanan = $this->dbLayanan;
        $dbAntrean = $this->dbAntrean;

        // Ambil riwayat layanan dari node mpp_layanan
        $riwayat = $dbLayanan
            ->table('riwayat_layanan')
            ->where('instansi_id', $instansiId)
            ->whereIn(
                'status_layanan',
                [
                    'DIPANGGIL',
                    'DILAYANI',
                    'PENDING',
                    'SELESAI',
                ]
            )
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        if (empty($riwayat)) {
            return [];
        }

        $hasil = [];
        $tanggalHariIni = date('Y-m-d');

        foreach ($riwayat as $row) {
            // Ambil data nomor antrean dari node mpp_antrean
            $antrean = $dbAntrean
                ->table('antrean')
                ->select(
                    'nomor_antrean, tanggal_antrean, jenis_antrean'
                )
                ->where('id', $row['antrean_id'])
                ->where('tanggal_antrean', $tanggalHariIni)
                ->get()
                ->getRowArray();

            // Kalau antreannya tidak ditemukan / bukan antrean hari ini,
            // jangan masukkan ke hasil.
            if (empty($antrean)) {
                continue;
            }

            $hasil[] = array_merge(
                $row,
                $antrean
            );
        }

        return $hasil;
    }

    /*
    |--------------------------------------------------------------------------
    | GET RIWAYAT LAYANAN
    |--------------------------------------------------------------------------
    */

    public function getRiwayatLayanan(
        int $riwayatLayananId
    ): ?array {
        $dbLayanan = $this->dbLayanan;
        $dbAntrean = $this->dbAntrean;

        // Ambil data riwayat layanan dari mpp_layanan
        $riwayat = $dbLayanan
            ->table('riwayat_layanan')
            ->where('id', $riwayatLayananId)
            ->get()
            ->getRowArray();

        if (empty($riwayat)) {
            return null;
        }

        // Ambil data antrean dari mpp_antrean
        $antrean = $dbAntrean
            ->table('antrean')
            ->select(
                'nomor_antrean, tanggal_antrean, jenis_antrean'
            )
            ->where('id', $riwayat['antrean_id'])
            ->get()
            ->getRowArray();

        if (empty($antrean)) {
            return null;
        }

        return array_merge(
            $riwayat,
            $antrean
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET RIWAYAT DENGAN LOCK
    |--------------------------------------------------------------------------
    */

    protected function getRiwayatDenganLock(
        int $riwayatLayananId
    ): ?array {
        return $this->dbLayanan
            ->query(
                'SELECT *
                FROM riwayat_layanan
                WHERE id = ?
                FOR UPDATE',
                [$riwayatLayananId]
            )
            ->getRowArray();
    }

    /*
    |--------------------------------------------------------------------------
    | GET NOMOR ANTREAN
    |--------------------------------------------------------------------------
    */

    protected function getNomorAntrean(
        int $antreanId
    ): int {
        $row = $this->dbAntrean
            ->table('antrean')
            ->select('nomor_antrean')
            ->where(
                'id',
                $antreanId
            )
            ->get()
            ->getRowArray();

        if (!$row) {
            throw new RuntimeException(
                'Data antrean tidak ditemukan.'
            );
        }

        return (int) $row['nomor_antrean'];
    }

    /*
    |--------------------------------------------------------------------------
    | GET JUMLAH ANTREAN INSTANSI
    |--------------------------------------------------------------------------
    */

    public function getJumlahAntreanInstansi(
        int $instansiId,
        string $jenisAntrean,
        ?string $tanggal = null
    ): int {
        $tanggal ??= date('Y-m-d');

        $jenisAntrean = strtoupper(trim($jenisAntrean));

        $this->validasiJenisAntrean($jenisAntrean);

        return $this->dbAntrean
            ->table('antrean')
            ->where('instansi_awal_id', $instansiId)
            ->where('tanggal_antrean', $tanggal)
            ->where('jenis_antrean', $jenisAntrean)
            ->countAllResults();
    }

    /*
    |--------------------------------------------------------------------------
    | GET KUOTA INSTANSI
    |--------------------------------------------------------------------------
    */

    public function getKuotaInstansi(
        int $instansiId,
        ?string $tanggal = null
    ): array {
        $tanggal ??= date('Y-m-d');

        $data = $this->kuotaInstansiModel
            ->where('instansi_id', $instansiId)
            ->where('tanggal', $tanggal)
            ->first();

        if (!$data) {
            return [
                'instansi_id'     => $instansiId,
                'tanggal'         => $tanggal,
                'kuota_biasa'     => self::KUOTA_BIASA_DEFAULT,
                'kuota_prioritas' => self::KUOTA_PRIORITAS_DEFAULT,
            ];
        }

        return [
            'instansi_id'     => $instansiId,
            'tanggal'         => $tanggal,
            'kuota_biasa'     => (int) $data['kuota_biasa'],
            'kuota_prioritas' => (int) $data['kuota_prioritas'],
        ];
    }

    // AMBIL KUOTA BERDASARKAN JENIS

    public function getKuotaInstansiByJenis(
        int $instansiId,
        string $jenisAntrean,
        ?string $tanggal = null
    ): int {
        $jenisAntrean = strtoupper(trim($jenisAntrean));

        $kuota = $this->getKuotaInstansi(
            $instansiId,
            $tanggal
        );

        return match ($jenisAntrean) {
            'BIASA' => $kuota['kuota_biasa'],
            'PRIORITAS' => $kuota['kuota_prioritas'],
            default => throw new \InvalidArgumentException(
                'Jenis antrean tidak valid.'
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | SET KUOTA INSTANSI
    |--------------------------------------------------------------------------
    */

    public function setKuotaInstansi(
        int $instansiId,
        int $kuotaBiasa,
        int $kuotaPrioritas,
        ?string $tanggal = null
    ): array {
        if ($instansiId < 1) {
            throw new \InvalidArgumentException(
                'Instansi tidak valid.'
            );
        }

        if ($kuotaBiasa < 1) {
            throw new \InvalidArgumentException(
                'Kuota biasa minimal 1 tiket.'
            );
        }

        if ($kuotaPrioritas < 1) {
            throw new \InvalidArgumentException(
                'Kuota prioritas minimal 1 tiket.'
            );
        }

        $tanggal ??= date('Y-m-d');

        $data = $this->kuotaInstansiModel
            ->where('instansi_id', $instansiId)
            ->where('tanggal', $tanggal)
            ->first();

        $payload = [
            'instansi_id'     => $instansiId,
            'tanggal'         => $tanggal,
            'kuota_biasa'     => $kuotaBiasa,
            'kuota_prioritas' => $kuotaPrioritas,
        ];

        if ($data) {
            $this->kuotaInstansiModel->update(
                $data['id'],
                [
                    'kuota_biasa'     => $kuotaBiasa,
                    'kuota_prioritas' => $kuotaPrioritas,
                ]
            );
        } else {
            $this->kuotaInstansiModel->insert($payload);
        }

        return $payload;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI JENIS ANTREAN
    |--------------------------------------------------------------------------
    */

    protected function validasiJenisAntrean(
        string $jenisAntrean
    ): void {
        if (!in_array(
            $jenisAntrean,
            [
                'BIASA',
                'PRIORITAS',
            ],
            true
        )) {
            throw new RuntimeException(
                'Jenis antrean tidak valid.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI HARI PELAYANAN
    |--------------------------------------------------------------------------
    |
    | Sabtu dan Minggu tidak tersedia.
    | Hari libur khusus membaca mpp_pusat.hari_libur.
    |
    */

    protected function validasiHariPelayanan(
        string $tanggal
    ): void {
        $hari = (int) date(
            'N',
            strtotime($tanggal)
        );

        /*
        |--------------------------------------------------------------------------
        | 6 = Sabtu
        | 7 = Minggu
        |--------------------------------------------------------------------------
        */

        if ($hari >= 6) {
            throw new RuntimeException(
                'Tanggal tersebut bukan hari pelayanan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Cek hari libur khusus
        |--------------------------------------------------------------------------
        */

        $libur = $this->dbPusat
            ->table('hari_libur')
            ->where(
                'tanggal',
                $tanggal
            )
            ->get()
            ->getRowArray();

        if ($libur) {
            $keterangan = $libur['keterangan']
                ? ' (' . $libur['keterangan'] . ')'
                : '';

            throw new RuntimeException(
                'Tanggal tersebut merupakan hari libur'
                . $keterangan
                . '.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI JAM PENGAMBILAN
    |--------------------------------------------------------------------------
    */

    protected function validasiJamPengambilan(
        string $tanggal
    ): void {
        // Pembatasan jam berlaku untuk antrean hari ini.
        // Booking tanggal mendatang tidak dibatasi oleh jam saat ini.
        if ($tanggal !== date('Y-m-d')) {
            return;
        }

        $sekarang = date('H:i:s');

        if (
            $sekarang < $this->jamMulaiAmbil
            || $sekarang > $this->jamSelesaiAmbil
        ) {
            throw new RuntimeException(
                'Pengambilan antrean hari ini hanya tersedia '
                . 'pukul 08:00 sampai 15:00.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI INSTANSI
    |--------------------------------------------------------------------------
    */

    protected function validasiInstansi(
        int $instansiId
    ): array {
        $instansi = $this->dbPusat
            ->table('instansi')
            ->where(
                'id',
                $instansiId
            )
            ->get()
            ->getRowArray();

        if (!$instansi) {
            throw new RuntimeException(
                'Instansi tidak ditemukan.'
            );
        }

        return $instansi;
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI LAYANAN
    |--------------------------------------------------------------------------
    |
    | Layanan harus milik instansi tempat pelayanan dilakukan.
    |
    */

    protected function validasiLayanan(
        int $layananId,
        int $instansiId
    ): void {
        $layanan = $this->dbPusat
            ->table('layanan')
            ->where(
                'id',
                $layananId
            )
            ->where(
                'instansi_id',
                $instansiId
            )
            ->get()
            ->getRowArray();

        if (!$layanan) {
            throw new RuntimeException(
                'Layanan tidak ditemukan atau bukan milik instansi tersebut.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDASI PETUGAS
    |--------------------------------------------------------------------------
    |
    | Memastikan:
    | - user ada
    | - user adalah Petugas
    | - user terdaftar pada instansi
    |
    */

    protected function validasiPetugasInstansi(
        int $petugasId,
        int $instansiId
    ): void {
        $petugas = $this->dbPusat
            ->table('users u')
            ->select('
                u.id,
                u.instansi_id,
                u.role_id,
                r.nama_role
            ')
            ->join(
                'roles r',
                'r.id = u.role_id'
            )
            ->where(
                'u.id',
                $petugasId
            )
            ->get()
            ->getRowArray();

        if (!$petugas) {
            throw new RuntimeException(
                'Petugas tidak ditemukan.'
            );
        }

        if ($petugas['nama_role'] !== 'Petugas') {
            throw new RuntimeException(
                'User tersebut bukan petugas.'
            );
        }

        if (
            $petugas['instansi_id'] === null
            || (int) $petugas['instansi_id'] !== $instansiId
        ) {
            throw new RuntimeException(
                'Petugas tidak terdaftar pada instansi tersebut.'
            );
        }
    }

}