<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// =========================================================
// TEMPORARY CORE SERVICE TEST
// =========================================================

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
    'test/core/booking-besok',
    'AntreanCoreTestController::bookingBesok'
);