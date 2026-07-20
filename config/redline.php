<?php

declare(strict_types=1);

return [
    'store_name' => env('REDLINE_STORE_NAME', 'Redline Komputer'),

    'wa_number' => env('REDLINE_WA_NUMBER', '6281234567890'),

    'metode_bayar' => ['Tunai', 'Transfer', 'QRIS'],

    'stok_kritis' => 5,

    'prefix_nota' => '',
    'prefix_resi' => 'PK',
];
