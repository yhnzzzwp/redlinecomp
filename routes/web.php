<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\PosController;
use App\Http\Controllers\Internal\ProdukController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.landing')->name('landing');
Route::view('/about', 'public.soon', ['judul' => 'About Us', 'aktif' => 'About Us'])->name('about');
Route::view('/catalogue', 'public.soon', ['judul' => 'Catalogue', 'aktif' => 'Catalogue'])->name('catalogue');
Route::view('/cek-servis', 'public.soon', ['judul' => 'Cek Status Servis', 'aktif' => 'Service'])->name('cek.servis');
Route::view('/cek-nota', 'public.soon', ['judul' => 'Cek Nota Transaksi', 'aktif' => 'Service'])->name('cek.nota');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.checkout');
    Route::get('/pos/nota/{transaksi}', [PosController::class, 'nota'])->name('pos.nota');

    Route::resource('produk', ProdukController::class)->except(['show']);
    Route::view('/customers', 'internal.soon', ['judul' => 'Customer Directory', 'aktif' => 'customers'])->name('customers');
    Route::view('/service', 'internal.soon', ['judul' => 'Service Management', 'aktif' => 'service'])->name('service');

    Route::middleware('owner')->group(function () {
        Route::view('/analytics', 'internal.soon', ['judul' => 'Sales Analytics', 'aktif' => 'analytics'])->name('analytics');
        Route::view('/promo', 'internal.soon', ['judul' => 'Manajemen Promo', 'aktif' => 'promo'])->name('promo');
        Route::view('/pegawai', 'internal.soon', ['judul' => 'Akun Pegawai', 'aktif' => 'pegawai'])->name('pegawai');
    });
});
