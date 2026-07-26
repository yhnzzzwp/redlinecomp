<?php

declare(strict_types=1);

return [
    'store_name' => env('REDLINE_STORE_NAME', 'Redline Komputer'),

    /*
     * Pemisahan portal per subdomain. Zona publik dilayani host utama;
     * login karyawan & admin masing-masing lewat subdomain sendiri sehingga
     * cookie sesi (host-only) dan permukaan serangan terpisah.
     * Lokal: admin.localhost / karyawan.localhost otomatis mengarah ke 127.0.0.1.
     */
    'hosts' => [
        'public' => env('REDLINE_PUBLIC_HOST', 'localhost'),
        'staff' => env('REDLINE_STAFF_HOST', 'karyawan.localhost'),
        'admin' => env('REDLINE_ADMIN_HOST', 'admin.localhost'),
    ],

    'wa_number' => env('REDLINE_WA_NUMBER', '6285640203069'),

    'metode_bayar' => ['Tunai', 'Transfer', 'QRIS'],

    'stok_kritis' => 5,

    'prefix_nota' => '',
    'prefix_resi' => 'PK',
];
