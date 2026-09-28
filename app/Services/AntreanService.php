<?php

namespace App\Services;

use App\Models\AntreanModel;
use App\Models\SequenceAntreanModel;
use App\Models\RiwayatLayananModel;
use App\Models\RiwayatPanggilanModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class AntreanService
{
    protected BaseConnection $dbTransaksi;
    protected BaseConnection $dbPusat;

    protected AntreanModel $antreanModel;
    protected SequenceAntreanModel $sequenceModel;
    protected RiwayatLayananModel $riwayatLayananModel;
    protected RiwayatPanggilanModel $riwayatPanggilanModel;

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
        $this->dbTransaksi = db_connect('transaksi');
        $this->dbPusat = db_connect();

        $this->antreanModel = new AntreanModel();
        $this->sequenceModel = new SequenceAntreanModel();
        $this->riwayatLayananModel = new RiwayatLayananModel();
        $this->riwayatPanggilanModel = new RiwayatPanggilanModel();
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

    /*
    |--------------------------------------------------------------------------
    | Validasi jenis antrean
    |--------------------------------------------------------------------------
    */
    $this->validasiJenisAntrean($jenisAntrean);

    /*
    |--------------------------------------------------------------------------
    | Validasi tanggal
    |--------------------------------------------------------------------------
    */
    $tanggalHariIni = date('Y-m-d');

    if ($tanggal < $tanggalHariIni) {
        throw new RuntimeException(
            'Tanggal antrean tidak boleh lebih kecil dari hari ini.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Booking maksimal 7 hari ke depan
    |--------------------------------------------------------------------------
    */
    $batasTanggal = date(
        'Y-m-d',
        strtotime($tanggalHariIni . ' +7 days')
    );

    if ($tanggal > $batasTanggal) {
        throw new RuntimeException(
            'Antrean hanya dapat diambil maksimal 1 minggu ke depan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi hari pelayanan
    |--------------------------------------------------------------------------
    */
    $this->validasiHariPelayanan($tanggal);

    /*
    |--------------------------------------------------------------------------
    | Jika mengambil antrean hari ini,
    | cek jam pengambilan.
    |
    | Jika tanggal lebih dari hari ini,
    | berarti masuk sebagai booking dan tidak
    | terkena batas jam pengambilan hari ini.
    |--------------------------------------------------------------------------
    */
    if ($tanggal === $tanggalHariIni) {
        $this->validasiJamPengambilan();
    }

    /*
    |--------------------------------------------------------------------------
    | Validasi instansi
    |--------------------------------------------------------------------------
    */
    $instansi = $this->dbPusat
        ->table('instansi')
        ->where('id', $instansiId)
        ->get()
        ->getRowArray();

    if (!$instansi) {
        throw new RuntimeException(
            'Instansi tidak ditemukan.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Mulai transaksi
    |--------------------------------------------------------------------------
    */
    $this->dbTransaksi->transBegin();

    try {
        /*
        |--------------------------------------------------------------------------
        | Generate nomor antrean
        |
        | PENTING:
        | Nomor dibuat berdasarkan $tanggal pelayanan,
        | bukan berdasarkan tanggal saat booking.
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
            'waktu_ambil'      => date('Y-m-d H:i:s'),
        ], true);

        if (!$antreanId) {
            throw new RuntimeException(
                'Gagal membuat data antrean.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Simpan riwayat layanan awal
        |--------------------------------------------------------------------------
        |
        | Layanan belum diketahui saat masyarakat mengambil antrean.
        | Layanan akan diisi oleh petugas ketika pelayanan dilakukan.
        |
        */
        $riwayatLayananId = $this->riwayatLayananModel->insert([
            'antrean_id'     => $antreanId,
            'instansi_id'    => $instansiId,
            'layanan_id'     => null,
            'petugas_id'     => null,
            'status_layanan' => 'MENUNGGU',
            'waktu_masuk'    => date('Y-m-d H:i:s'),
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

        /*
        |--------------------------------------------------------------------------
        | Cek transaksi
        |--------------------------------------------------------------------------
        */
        if ($this->dbTransaksi->transStatus() === false) {
            throw new RuntimeException(
                'Transaksi pengambilan antrean gagal.'
            );
        }

        $this->dbTransaksi->transCommit();

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
                'waktu_ambil'        => date('Y-m-d H:i:s'),
            ],
        ];

    } catch (\Throwable $e) {
        $this->dbTransaksi->transRollback();

        throw $e;
    }
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
        $builder = $this->dbTransaksi
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

        $sequence = $this->dbTransaksi
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

        $this->dbTransaksi
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

        $this->dbTransaksi->transBegin();

        try {
            /*
            |--------------------------------------------------------------------------
            | Pastikan tidak ada antrean aktif
            |--------------------------------------------------------------------------
            */

            $layananAktif = $this->dbTransaksi
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
            | Cari antrean MENUNGGU
            |--------------------------------------------------------------------------
            |
            | PRIORITAS → BIASA → FIFO
            |
            | PENDING sengaja tidak dimasukkan.
            |
            */

            $antrean = $this->dbTransaksi
                ->table('riwayat_layanan rl')
                ->select('
                    rl.id AS riwayat_layanan_id,
                    rl.antrean_id,
                    rl.instansi_id,
                    rl.layanan_id,
                    rl.status_layanan,
                    rl.waktu_masuk,
                    a.nomor_antrean,
                    a.tanggal_antrean,
                    a.jenis_antrean
                ')
                ->join(
                    'antrean a',
                    'a.id = rl.antrean_id'
                )
                ->where(
                    'rl.instansi_id',
                    $instansiId
                )
                ->where(
                    'rl.status_layanan',
                    'MENUNGGU'
                )
                ->where(
                    'a.tanggal_antrean',
                    date('Y-m-d')
                )
                ->orderBy(
                    "CASE
                        WHEN a.jenis_antrean = 'PRIORITAS' THEN 0
                        ELSE 1
                    END",
                    'ASC',
                    false
                )
                ->orderBy(
                    'rl.waktu_masuk',
                    'ASC'
                )
                ->orderBy(
                    'rl.id',
                    'ASC'
                )
                ->limit(1)
                ->get()
                ->getRowArray();

            if (!$antrean) {
                throw new RuntimeException(
                    'Tidak ada antrean yang dapat dipanggil.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Lock antrean terpilih
            |--------------------------------------------------------------------------
            */

            $locked = $this->dbTransaksi
                ->query(
                    'SELECT id, status_layanan
                     FROM riwayat_layanan
                     WHERE id = ?
                     FOR UPDATE',
                    [$antrean['riwayat_layanan_id']]
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
            | Karena tidak ada tombol "Mulai Layanan",
            | setelah dipanggil antrean langsung dianggap DILAYANI.
            |--------------------------------------------------------------------------
            */

            $this->dbTransaksi
                ->table('riwayat_layanan')
                ->where(
                    'id',
                    $antrean['riwayat_layanan_id']
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

            $this->dbTransaksi
                ->table('riwayat_panggilan')
                ->insert([
                    'riwayat_layanan_id' =>
                        $antrean['riwayat_layanan_id'],
                    'petugas_id' => $petugasId,
                    'aksi' => 'PANGGIL',
                    'waktu' => $waktuPanggil,
                    'keterangan' => null,
                    'created_at' => $waktuPanggil,
                    'updated_at' => $waktuPanggil,
                ]);

            if ($this->dbTransaksi->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal memproses pemanggilan antrean.'
                );
            }

            $this->dbTransaksi->transCommit();

            return [
                'riwayat_layanan_id' =>
                    (int) $antrean['riwayat_layanan_id'],

                'antrean_id' =>
                    (int) $antrean['antrean_id'],

                'nomor_antrean' =>
                    (int) $antrean['nomor_antrean'],

                'jenis_antrean' =>
                    $antrean['jenis_antrean'],

                'instansi_id' =>
                    (int) $antrean['instansi_id'],

                'petugas_id' =>
                    $petugasId,

                'status_layanan' =>
                    'DILAYANI',

                'aksi' =>
                    'PANGGIL',

                'waktu' =>
                    $waktuPanggil,
            ];

        } catch (Throwable $e) {
            $this->dbTransaksi->transRollback();

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
        $this->dbTransaksi->transBegin();

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

            $this->dbTransaksi
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

            if ($this->dbTransaksi->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal menyimpan riwayat panggilan ulang.'
                );
            }

            $this->dbTransaksi->transCommit();

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

        } catch (Throwable $e) {
            $this->dbTransaksi->transRollback();

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

        $this->dbTransaksi->transBegin();

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
                | Pastikan layanan milik instansi
                |--------------------------------------------------------------------------
                */

                $this->validasiLayanan(
                    $layananId,
                    (int) $riwayat['instansi_id']
                );

                $this->dbTransaksi
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
            */

            if ($status === 'PENDING') {

                /*
                |--------------------------------------------------------------------------
                | Pending tidak membutuhkan layanan.
                |
                | Kalau dari request dikirim layanan_id,
                | sengaja tidak digunakan.
                |--------------------------------------------------------------------------
                */

                $this->dbTransaksi
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

            if ($this->dbTransaksi->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal mengubah status antrean.'
                );
            }

            $this->dbTransaksi->transCommit();

            return $this->getRiwayatLayanan(
                $riwayatLayananId
            );

        } catch (Throwable $e) {
            $this->dbTransaksi->transRollback();

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
        $this->dbTransaksi->transBegin();

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
            | Pastikan pending
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

            $layananAktif = $this->dbTransaksi
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

            $this->dbTransaksi
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

            $this->dbTransaksi
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

            if ($this->dbTransaksi->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal memanggil antrean pending.'
                );
            }

            $this->dbTransaksi->transCommit();

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

        } catch (Throwable $e) {
            $this->dbTransaksi->transRollback();

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
        $this->dbTransaksi->transBegin();

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
            | Cek apakah antrean sudah punya perjalanan aktif
            | di instansi tujuan.
            |--------------------------------------------------------------------------
            */

            $sudahAda = $this->dbTransaksi
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

            $this->dbTransaksi
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
            | belum diketahui sampai petugas tujuan melayani.
            |
            */

            $this->dbTransaksi
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

            $riwayatBaruId = $this->dbTransaksi->insertID();

            if (!$riwayatBaruId) {
                throw new RuntimeException(
                    'Gagal membuat riwayat layanan tujuan.'
                );
            }

            if ($this->dbTransaksi->transStatus() === false) {
                throw new RuntimeException(
                    'Gagal memproses terusan antrean.'
                );
            }

            $this->dbTransaksi->transCommit();

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

        } catch (Throwable $e) {
            $this->dbTransaksi->transRollback();

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
        return $this->dbTransaksi
            ->table('riwayat_layanan rl')
            ->select('
                rl.*,
                a.nomor_antrean,
                a.tanggal_antrean,
                a.jenis_antrean
            ')
            ->join(
                'antrean a',
                'a.id = rl.antrean_id'
            )
            ->where(
                'rl.instansi_id',
                $instansiId
            )
            ->whereIn(
                'rl.status_layanan',
                [
                    'DIPANGGIL',
                    'DILAYANI',
                ]
            )
            ->where(
                'a.tanggal_antrean',
                date('Y-m-d')
            )
            ->orderBy(
                'rl.id',
                'DESC'
            )
            ->limit(1)
            ->get()
            ->getRowArray();
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
        return $this->dbTransaksi
            ->table('riwayat_layanan rl')
            ->select('
                rl.*,
                a.nomor_antrean,
                a.tanggal_antrean,
                a.jenis_antrean
            ')
            ->join(
                'antrean a',
                'a.id = rl.antrean_id'
            )
            ->where(
                'rl.instansi_id',
                $instansiId
            )
            ->where(
                'rl.status_layanan',
                'MENUNGGU'
            )
            ->where(
                'a.tanggal_antrean',
                date('Y-m-d')
            )
            ->orderBy(
                "CASE
                    WHEN a.jenis_antrean = 'PRIORITAS' THEN 0
                    ELSE 1
                END",
                'ASC',
                false
            )
            ->orderBy(
                'rl.waktu_masuk',
                'ASC'
            )
            ->orderBy(
                'rl.id',
                'ASC'
            )
            ->get()
            ->getResultArray();
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
        return $this->dbTransaksi
            ->table('riwayat_layanan rl')
            ->select('
                rl.*,
                a.nomor_antrean,
                a.tanggal_antrean,
                a.jenis_antrean
            ')
            ->join(
                'antrean a',
                'a.id = rl.antrean_id'
            )
            ->where(
                'rl.instansi_id',
                $instansiId
            )
            ->where(
                'a.tanggal_antrean',
                date('Y-m-d')
            )
            ->whereIn(
                'rl.status_layanan',
                [
                    'DIPANGGIL',
                    'DILAYANI',
                    'PENDING',
                    'SELESAI',
                ]
            )
            ->orderBy(
                'rl.updated_at',
                'DESC'
            )
            ->orderBy(
                'rl.id',
                'DESC'
            )
            ->get()
            ->getResultArray();
    }

    /*
    |--------------------------------------------------------------------------
    | GET RIWAYAT LAYANAN
    |--------------------------------------------------------------------------
    */

    public function getRiwayatLayanan(
        int $riwayatLayananId
    ): ?array {
        return $this->dbTransaksi
            ->table('riwayat_layanan rl')
            ->select('
                rl.*,
                a.nomor_antrean,
                a.tanggal_antrean,
                a.jenis_antrean
            ')
            ->join(
                'antrean a',
                'a.id = rl.antrean_id'
            )
            ->where(
                'rl.id',
                $riwayatLayananId
            )
            ->get()
            ->getRowArray();
    }

    /*
    |--------------------------------------------------------------------------
    | GET RIWAYAT DENGAN LOCK
    |--------------------------------------------------------------------------
    */

    protected function getRiwayatDenganLock(
        int $riwayatLayananId
    ): ?array {
        return $this->dbTransaksi
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
        $row = $this->dbTransaksi
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

    protected function validasiJamPengambilan(): void
    {
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
    ): void {
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

    // =========================================================
    // TESTING KHUSUS BPKD (Instansi ID: 6, Petugas ID: 3)
    // =========================================================
    
   
}