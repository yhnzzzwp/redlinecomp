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

/*
| robots.txt dinamis: host publik boleh diindeks, subdomain portal tidak.
| (Header X-Robots-Tag di SecurityHeaders adalah lapisan keduanya.)
*/
Route::get('/robots.txt', function () {
    $isPublik = \App\Support\Portal::fromRequest(request()) === \App\Support\Portal::Publik;

    return response($isPublik ? "User-agent: *\nDisallow:\n" : "User-agent: *\nDisallow: /\n", 200)
        ->header('Content-Type', 'text/plain');
});

/*
|--------------------------------------------------------------------------
| Zona publik — hanya host utama (config redline.hosts.public)
|--------------------------------------------------------------------------
| Diakses lewat host admin/karyawan → dialihkan ke login portal tersebut.
*/
Route::middleware('portal:public')->group(function () {
    // Beranda langsung menampilkan katalog (hero + grid produk).
    Route::get('/', [PublicController::class, 'landing'])->name('landing');
    Route::get('/about', [PublicController::class, 'about'])->name('about');
    Route::view('/toko-ikan', 'public.soon', ['judul' => 'Toko Ikan Redline', 'aktif' => 'Toko Ikan'])->name('toko-ikan');

    // Alamat lama /catalogue dialihkan ke beranda (bookmark tetap hidup).
    Route::get('/catalogue', fn (\Illuminate\Http\Request $r) => redirect()->route('landing', $r->query()))->name('catalogue');
    Route::get('/catalogue/{produk}', [PublicController::class, 'detailProduk'])->name('catalogue.show');

    Route::get('/cek-servis', [PublicController::class, 'cekServis'])->middleware('throttle:10,1')->name('cek.servis');
});

/*
|--------------------------------------------------------------------------
| Zona internal — hanya subdomain portal (admin.* / karyawan.*)
|--------------------------------------------------------------------------
| Dari host publik seluruh route ini 404 (keberadaan portal disembunyikan).
| Role user wajib cocok dengan portal: Owner ↔ admin, Karyawan ↔ karyawan.
*/
Route::middleware('portal:internal')->group(function () {
    // Manifest PWA: tanpa auth (browser mengambilnya tanpa cookie sesi),
    // tetap 404 dari host publik karena berada di grup portal:internal.
    Route::get('/manifest.webmanifest', [\App\Http\Controllers\Internal\PwaController::class, 'manifest'])->name('pwa.manifest');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::get('/login/2fa', [\App\Http\Controllers\Internal\TotpController::class, 'tantangan'])->name('totp.tantangan');
        Route::post('/login/2fa', [\App\Http\Controllers\Internal\TotpController::class, 'verifikasi'])->middleware('throttle:10,1')->name('totp.verifikasi');
    });
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.checkout');
        Route::get('/pos/nota/{transaksi}', [PosController::class, 'nota'])->name('pos.nota');
        Route::get('/pos/struk/{transaksi}', [PosController::class, 'struk'])->name('pos.struk');

        Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');

        Route::get('/produk/template-excel', [ProdukController::class, 'template'])->name('produk.template');
        Route::get('/produk/export-excel', [ProdukController::class, 'export'])->name('produk.export');
        Route::post('/produk/import-excel', [ProdukController::class, 'import'])->name('produk.import');
        Route::resource('produk', ProdukController::class)->except(['show']);

        Route::get('/stok/opname', [\App\Http\Controllers\Internal\StokController::class, 'opname'])->name('stok.opname');
        Route::post('/stok/opname', [\App\Http\Controllers\Internal\StokController::class, 'simpanOpname'])->name('stok.opname.simpan');
        Route::get('/stok/mutasi', [\App\Http\Controllers\Internal\StokController::class, 'mutasi'])->name('stok.mutasi');

        Route::get('/service', [ServiceController::class, 'index'])->name('service');
        Route::get('/service/create', [ServiceController::class, 'create'])->name('service.create');
        Route::post('/service', [ServiceController::class, 'store'])->name('service.store');
        Route::get('/service/{service}', [ServiceController::class, 'show'])->name('service.show');
        Route::post('/service/{service}/status', [ServiceController::class, 'updateStatus'])->name('service.status');
        Route::post('/service/{service}/part', [ServiceController::class, 'storePart'])->name('service.part');
        Route::delete('/service/{service}/part/{part}', [ServiceController::class, 'destroyPart'])->name('service.part.destroy');

        Route::middleware('owner')->group(function () {
            Route::get('/keamanan', [\App\Http\Controllers\Internal\TotpController::class, 'kelola'])->name('keamanan');
            Route::post('/keamanan/2fa/aktifkan', [\App\Http\Controllers\Internal\TotpController::class, 'aktifkan'])->name('totp.aktifkan');
            Route::post('/keamanan/2fa/nonaktifkan', [\App\Http\Controllers\Internal\TotpController::class, 'nonaktifkan'])->name('totp.nonaktifkan');
            Route::post('/transaksi/{transaksi}/void', [TransaksiController::class, 'void'])->name('transaksi.void');
            Route::get('/transaksi/export-csv', [TransaksiController::class, 'exportCsv'])->name('transaksi.export');
            Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
            Route::get('/analytics/cetak', [AnalyticsController::class, 'cetak'])->name('analytics.cetak');
            Route::get('/analytics/export-csv', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');
            Route::resource('promo', PromoController::class)->except(['show']);
            Route::resource('pegawai', PegawaiController::class)->except(['show']);
        });
    });
});
