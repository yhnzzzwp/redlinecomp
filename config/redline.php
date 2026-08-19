<?php

declare(strict_types=1);

return [
    'store_name' => env('REDLINE_STORE_NAME', 'Redline Komputer'),

    'hosts' => [
        'public' => env('REDLINE_PUBLIC_HOST', 'localhost'),
        'staff' => env('REDLINE_STAFF_HOST', 'karyawan.localhost'),
        'admin' => env('REDLINE_ADMIN_HOST', 'admin.localhost'),
    ],

    'wa_number' => env('REDLINE_WA_NUMBER', '6285640203069'),

    'metode_bayar' => ['Tunai', 'Transfer', 'QRIS'],

    'prefix_nota' => '',
    'prefix_resi' => 'PK',
];
