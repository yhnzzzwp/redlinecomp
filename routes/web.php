<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\PosController;
use App\Http\Controllers\Internal\ProdukController;
use App\Http\Controllers\Internal\PromoController;
use App\Http\Controllers\Internal\ServiceController;
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

    Route::get('/service', [ServiceController::class, 'index'])->name('service');
    Route::get('/service/create', [ServiceController::class, 'create'])->name('service.create');
    Route::post('/service', [ServiceController::class, 'store'])->name('service.store');
    Route::get('/service/{service}', [ServiceController::class, 'show'])->name('service.show');
    Route::post('/service/{service}/status', [ServiceController::class, 'updateStatus'])->name('service.status');
    Route::post('/service/{service}/part', [ServiceController::class, 'storePart'])->name('service.part');

    Route::middleware('owner')->group(function () {
        Route::view('/analytics', 'internal.soon', ['judul' => 'Sales Analytics', 'aktif' => 'analytics'])->name('analytics');
        Route::resource('promo', PromoController::class)->except(['show']);
        Route::view('/pegawai', 'internal.soon', ['judul' => 'Akun Pegawai', 'aktif' => 'pegawai'])->name('pegawai');
    });
});
