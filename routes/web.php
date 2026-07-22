<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Public\PublicController;
use App\Http\Controllers\Internal\AnalyticsController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\PegawaiController;
use App\Http\Controllers\Internal\PosController;
use App\Http\Controllers\Internal\ProdukController;
use App\Http\Controllers\Internal\PromoController;
use App\Http\Controllers\Internal\ServiceController;
use App\Http\Controllers\Internal\TransaksiController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.landing')->name('landing');
Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/catalogue', [PublicController::class, 'catalogue'])->name('catalogue');
Route::get('/catalogue/{produk}', [PublicController::class, 'detailProduk'])->name('catalogue.show');
Route::get('/cek-servis', [PublicController::class, 'cekServis'])->middleware('throttle:10,1')->name('cek.servis');
Route::get('/cek-nota', [PublicController::class, 'cekNota'])->middleware('throttle:10,1')->name('cek.nota');

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
    
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::post('/transaksi/{transaksi}/void', [TransaksiController::class, 'void'])->name('transaksi.void');

    Route::resource('produk', ProdukController::class)->except(['show']);

    Route::get('/service', [ServiceController::class, 'index'])->name('service');
    Route::get('/service/create', [ServiceController::class, 'create'])->name('service.create');
    Route::post('/service', [ServiceController::class, 'store'])->name('service.store');
    Route::get('/service/{service}', [ServiceController::class, 'show'])->name('service.show');
    Route::post('/service/{service}/status', [ServiceController::class, 'updateStatus'])->name('service.status');
    Route::post('/service/{service}/part', [ServiceController::class, 'storePart'])->name('service.part');

    Route::middleware('owner')->group(function () {
        Route::get('/transaksi/export-csv', [TransaksiController::class, 'exportCsv'])->name('transaksi.export');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/analytics/cetak', [AnalyticsController::class, 'cetak'])->name('analytics.cetak');
        Route::get('/analytics/export-csv', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');
        Route::resource('promo', PromoController::class)->except(['show']);
        Route::resource('pegawai', PegawaiController::class)->except(['show']);
    });
});
