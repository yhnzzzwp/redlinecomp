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

Route::get('/robots.txt', function () {
    $isPublik = \App\Support\Portal::fromRequest(request()) === \App\Support\Portal::Publik;

    return response($isPublik ? "User-agent: *\nDisallow:\n" : "User-agent: *\nDisallow: /\n", 200)
        ->header('Content-Type', 'text/plain');
});

Route::middleware('portal:public')->group(function () {

    Route::get('/', [PublicController::class, 'landing'])->name('landing');
    Route::get('/about', [PublicController::class, 'about'])->name('about');
    Route::view('/toko-ikan', 'public.soon', ['judul' => 'Toko Ikan Redline', 'aktif' => 'Toko Ikan'])->name('toko-ikan');

    Route::get('/catalogue', fn (\Illuminate\Http\Request $r) => redirect()->route('landing', $r->query()))->name('catalogue');
    Route::get('/catalogue/{produk}', [PublicController::class, 'detailProduk'])->name('catalogue.show');

    Route::get('/cek-servis', [PublicController::class, 'cekServis'])->middleware('throttle:10,1')->name('cek.servis');
});

Route::middleware('portal:internal')->group(function () {

    Route::get('/manifest.webmanifest', [\App\Http\Controllers\Internal\PwaController::class, 'manifest'])->name('pwa.manifest');

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
        Route::get('/pos/struk/{transaksi}', [PosController::class, 'struk'])->name('pos.struk');

        Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');

        Route::get('/sesi', [\App\Http\Controllers\Internal\SesiController::class, 'index'])->name('sesi');
        Route::delete('/sesi/{id}', [\App\Http\Controllers\Internal\SesiController::class, 'keluarkan'])->where('id', '[A-Za-z0-9]+')->name('sesi.keluarkan');
        Route::post('/sesi/keluarkan-lain', [\App\Http\Controllers\Internal\SesiController::class, 'keluarkanLain'])->name('sesi.keluarkan-lain');

        Route::get('/produk/template-excel', [ProdukController::class, 'template'])->name('produk.template');
        Route::get('/produk/export-excel', [ProdukController::class, 'export'])->name('produk.export');
        Route::post('/produk/import-excel', [ProdukController::class, 'import'])->name('produk.import');
        Route::resource('produk', ProdukController::class)->except(['show']);

        Route::get('/service', [ServiceController::class, 'index'])->name('service');
        Route::get('/service/create', [ServiceController::class, 'create'])->name('service.create');
        Route::post('/service', [ServiceController::class, 'store'])->name('service.store');
        Route::get('/service/{service}', [ServiceController::class, 'show'])->name('service.show');
        Route::post('/service/{service}/status', [ServiceController::class, 'updateStatus'])->name('service.status');
        Route::post('/service/{service}/part', [ServiceController::class, 'storePart'])->name('service.part');
        Route::delete('/service/{service}/part/{part}', [ServiceController::class, 'destroyPart'])->name('service.part.destroy');

        Route::middleware('owner')->group(function () {
            Route::post('/transaksi/{transaksi}/void', [TransaksiController::class, 'void'])->name('transaksi.void');
            Route::get('/transaksi/export-csv', [TransaksiController::class, 'exportCsv'])->name('transaksi.export');
            Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
            Route::get('/analytics/cetak', [AnalyticsController::class, 'cetak'])->name('analytics.cetak');
            Route::get('/analytics/export-csv', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');
            Route::resource('promo', PromoController::class)->except(['show']);
            Route::resource('pegawai', PegawaiController::class)->except(['show']);

            Route::delete('/pegawai/{pegawai}/sesi', [\App\Http\Controllers\Internal\SesiController::class, 'keluarkanPegawai'])->name('pegawai.sesi.keluarkan');
        });
    });
});
