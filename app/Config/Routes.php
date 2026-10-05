<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// =========================================================
// ANTREAN CORE TEST
// =========================================================

$routes->get('/', 'Home::index');

$routes->get(
    'test/core/ambil-biasa',
    'AntreanCoreTestController::ambilBiasa'
);

$routes->get(
    'test/core/ambil-prioritas',
    'AntreanCoreTestController::ambilPrioritas'
);

$routes->get(
    'test/core/panggil',
    'AntreanCoreTestController::panggilSelanjutnya'
);

$routes->get(
    'test/core/panggil-ulang/(:num)',
    'AntreanCoreTestController::panggilUlang/$1'
);

$routes->get(
    'test/core/pending/(:num)',
    'AntreanCoreTestController::pending/$1'
);

$routes->get(
    'test/core/selesai/(:num)/(:num)',
    'AntreanCoreTestController::selesai/$1/$2'
);

$routes->get(
    'test/core/panggil-pending/(:num)',
    'AntreanCoreTestController::panggilPending/$1'
);

$routes->get(
    'test/core/sedang-dilayani',
    'AntreanCoreTestController::sedangDilayani'
);

$routes->get(
    'test/core/selanjutnya',
    'AntreanCoreTestController::antreanSelanjutnya'
);

$routes->get(
    'test/core/sudah-dipanggil',
    'AntreanCoreTestController::sudahDipanggil'
);

$routes->get(
    'test/core/booking-besok/(:segment)',
    'AntreanCoreTestController::bookingBesok/$1'
);

$routes->get(
    'test/core/booking/(:segment)/(:segment)',
    'AntreanCoreTestController::booking/$1/$2'
);

$routes->get(
    'test/core/terusan/(:num)/(:num)/(:num)',
    'AntreanCoreTestController::terusan/$1/$2/$3'
);


// =========================================================
// AUTH
// =========================================================

$routes->post(
    'login',
    'AuthController::login'
);

$routes->post(
    'logout',
    'AuthController::logout',
    ['filter' => 'auth']
);


// =========================================================
// ADMIN
// =========================================================

$routes->group('admin', ['filter' => 'auth'], static function ($routes) {

    // =========================
    // DASHBOARD
    // =========================

    $routes->get(
        'dashboard',
        'AdminController::dashboard',
        ['filter' => 'role:1']
    );


    // =========================
    // PETUGAS
    // =========================

    $routes->get(
        'petugas',
        'AdminPetugasController::index',
        ['filter' => 'role:1']
    );

    $routes->post(
        'petugas',
        'AdminPetugasController::create',
        ['filter' => 'role:1']
    );

    $routes->put(
        'petugas/(:num)',
        'AdminPetugasController::update/$1',
        ['filter' => 'role:1']
    );

    $routes->delete(
        'petugas/(:num)',
        'AdminPetugasController::delete/$1',
        ['filter' => 'role:1']
    );


    // =========================
    // INSTANSI
    // =========================

    $routes->get(
        'instansi',
        'AdminInstansiController::index',
        ['filter' => 'role:1']
    );

    $routes->post(
        'instansi',
        'AdminInstansiController::create',
        ['filter' => 'role:1']
    );

    $routes->put(
        'instansi/(:num)',
        'AdminInstansiController::update/$1',
        ['filter' => 'role:1']
    );

    $routes->delete(
        'instansi/(:num)',
        'AdminInstansiController::delete/$1',
        ['filter' => 'role:1']
    );


    // =========================
    // GRUP
    // =========================

    $routes->get(
        'grup',
        'AdminGrupController::index',
        ['filter' => 'role:1']
    );

    $routes->post(
        'grup',
        'AdminGrupController::create',
        ['filter' => 'role:1']
    );

    $routes->put(
        'grup/(:num)',
        'AdminGrupController::update/$1',
        ['filter' => 'role:1']
    );

    $routes->delete(
        'grup/(:num)',
        'AdminGrupController::delete/$1',
        ['filter' => 'role:1']
    );


    // =========================
    // KELOMPOK
    // =========================

    $routes->get(
        'kelompok',
        'AdminKelompokController::index',
        ['filter' => 'role:1']
    );

    $routes->post(
        'kelompok',
        'AdminKelompokController::create',
        ['filter' => 'role:1']
    );

    $routes->put(
        'kelompok/(:num)',
        'AdminKelompokController::update/$1',
        ['filter' => 'role:1']
    );

    $routes->delete(
        'kelompok/(:num)',
        'AdminKelompokController::delete/$1',
        ['filter' => 'role:1']
    );


    // =========================
    // LAYANAN
    // =========================

    $routes->get(
        'layanan',
        'AdminLayananController::index',
        ['filter' => 'role:1']
    );

    $routes->post(
        'layanan',
        'AdminLayananController::create',
        ['filter' => 'role:1']
    );

    $routes->put(
        'layanan/(:num)',
        'AdminLayananController::update/$1',
        ['filter' => 'role:1']
    );

    $routes->delete(
        'layanan/(:num)',
        'AdminLayananController::delete/$1',
        ['filter' => 'role:1']
    );


    // =========================
    // HARI LIBUR
    // =========================

    $routes->get(
        'hari-libur',
        'AdminHariLiburController::index',
        ['filter' => 'role:1']
    );

    $routes->post(
        'hari-libur',
        'AdminHariLiburController::create',
        ['filter' => 'role:1']
    );

    $routes->put(
        'hari-libur/(:num)',
        'AdminHariLiburController::update/$1',
        ['filter' => 'role:1']
    );

    $routes->delete(
        'hari-libur/(:num)',
        'AdminHariLiburController::delete/$1',
        ['filter' => 'role:1']
    );
});


// =========================================================
// PETUGAS
// =========================================================

$routes->group('petugas', ['filter' => 'auth'], static function ($routes) {

    // =========================
    // DASHBOARD
    // =========================

    $routes->get(
        'dashboard',
        'PetugasController::dashboard',
        ['filter' => 'role:2']
    );


    // =========================
    // ANTREAN
    // =========================

    // Sedang dilayani
    $routes->get(
        'antrean/sedang-dilayani',
        'PetugasAntreanController::sedangDilayani',
        ['filter' => 'role:2']
    );

    // Antrean menunggu
    $routes->get(
        'antrean/menunggu',
        'PetugasAntreanController::menunggu',
        ['filter' => 'role:2']
    );

    // Panggil antrean berikutnya
    $routes->post(
        'antrean/panggil',
        'PetugasAntreanController::panggil',
        ['filter' => 'role:2']
    );

    // Panggil ulang
    $routes->post(
        'antrean/panggil-ulang',
        'PetugasAntreanController::panggilUlang',
        ['filter' => 'role:2']
    );

    // Konfirmasi status:
    // SELESAI / PENDING
    $routes->post(
        'antrean/status',
        'PetugasAntreanController::konfirmasiStatus',
        ['filter' => 'role:2']
    );

    // Panggil antrean pending
    $routes->post(
        'antrean/panggil-pending',
        'PetugasAntreanController::panggilPending',
        ['filter' => 'role:2']
    );

    // Antrean yang sudah dipanggil
    $routes->get(
        'antrean/sudah-dipanggil',
        'PetugasAntreanController::sudahDipanggil',
        ['filter' => 'role:2']
    );

    // Terusan antrean
    $routes->post(
        'antrean/terusan',
        'PetugasAntreanController::terusan',
        ['filter' => 'role:2']
    );
});

    //Masyarakat
    $routes->group('masyarakat', static function ($routes) {
        $routes->get('instansi', 
        'MasyarakatController::instansi');

        $routes->get('kuota/(:num)', 
        'MasyarakatController::kuota/$1');
        $routes->post('antrean', 
        'MasyarakatController::ambilAntrean');
});

    //Display
    $routes->get(
        'display/kelompok/(:num)',
        'DisplayController::kelompok/$1'
    );

    //Subdisplay
    $routes->get(
        'subdisplay/grup/(:num)',
        'SubdisplayController::grup/$1'
);