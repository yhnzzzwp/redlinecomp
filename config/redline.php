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

    /*
     * Bagan akun untuk Ekspor Jurnal Akuntansi (menu Analytics, Owner).
     * Kode bawaan mengikuti pola umum software akuntansi Indonesia
     * (Accurate/Zahir/Jurnal.id) — sesuaikan dengan bagan akun akuntan Anda;
     * ekspor otomatis mengikuti nilai di sini tanpa perlu mengubah kode.
     */
    'akun' => [
        'kas' => ['kode' => '1-10001', 'nama' => 'Kas'],
        'bank' => ['kode' => '1-10002', 'nama' => 'Bank'],
        'qris' => ['kode' => '1-10003', 'nama' => 'Bank — QRIS'],
        'persediaan' => ['kode' => '1-10300', 'nama' => 'Persediaan Barang'],
        'penjualan_produk' => ['kode' => '4-10001', 'nama' => 'Pendapatan Penjualan Produk'],
        'pendapatan_servis' => ['kode' => '4-10002', 'nama' => 'Pendapatan Jasa Servis'],
        'diskon_penjualan' => ['kode' => '4-20001', 'nama' => 'Diskon Penjualan'],
        'hpp' => ['kode' => '5-10001', 'nama' => 'Harga Pokok Penjualan'],
    ],

    'stok_kritis' => 5,

    'prefix_nota' => '',
    'prefix_resi' => 'PK',
];
