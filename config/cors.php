<?php

declare(strict_types=1);

/**
 * Origin yang boleh memanggil /api/v1 dari browser.
 *
 * Sebelumnya bernilai ['*']. Karena API ini memakai Bearer token (bukan cookie)
 * dan supports_credentials=false, dampak langsungnya terbatas — tetapi wildcard
 * menghapus satu lapis pertahanan dan menjadi celah serius begitu ada yang
 * menyalakan supports_credentials di kemudian hari.
 *
 * Isi CORS_ALLOWED_ORIGINS dengan daftar dipisah koma, mis:
 *   CORS_ALLOWED_ORIGINS=https://redlinekomputer.com,https://fe-redline.vercel.app
 */
$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

if ($origins === []) {
    // Default aman: hanya aplikasi ini sendiri. Frontend di domain lain WAJIB
    // didaftarkan lewat CORS_ALLOWED_ORIGINS saat deploy.
    $origins = array_values(array_filter([
        (string) env('APP_URL', 'http://localhost:8000'),
    ]));
}

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],
    'exposed_headers' => [],
    'max_age' => 600,
    'supports_credentials' => false,
];
