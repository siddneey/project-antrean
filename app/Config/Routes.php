<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */




$routes->get('/', 'Home::index');





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