<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiDashboardController;
use App\Http\Controllers\Api\ApiKatalogController;
use App\Http\Controllers\Api\ApiPegawaiController;
use App\Http\Controllers\Api\ApiPosController;
use App\Http\Controllers\Api\ApiProdukController;
use App\Http\Controllers\Api\ApiPromoController;
use App\Http\Controllers\Api\ApiPromoManagementController;
use App\Http\Controllers\Api\ApiServiceController;
use App\Http\Controllers\Api\ApiServiceManagementController;
use App\Http\Controllers\Api\ApiTransaksiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // --- Public API Routes ---
    Route::get('/health', [ApiAuthController::class, 'health']);
    Route::post('/auth/login', [ApiAuthController::class, 'login']);

    // Public Katalog & Kategori
    Route::get('/katalog', [ApiKatalogController::class, 'index']);
    Route::get('/katalog/{produk}', [ApiKatalogController::class, 'show']);
    Route::get('/kategori', [ApiKatalogController::class, 'kategori']);

    // Public Promo
    Route::get('/promo', [ApiPromoController::class, 'index']);
    Route::post('/promo/cek', [ApiPromoController::class, 'cek']);

    // Public Servis & Perangkat Tracking
    Route::get('/service/cek', [ApiServiceController::class, 'cek']);
    Route::get('/perangkat/{kode}', [ApiServiceController::class, 'perangkat']);

    // Offline POS Sync
    Route::post('/pos/sync', [ApiPosController::class, 'sync']);

    // --- Authenticated Staff & Admin API Routes ---
    Route::middleware('auth.api')->group(function (): void {
        // Current User Profile & Session
        Route::get('/auth/me', [ApiAuthController::class, 'me']);
        Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
        Route::put('/auth/profile', [ApiAuthController::class, 'updateProfile']);
        Route::put('/auth/password', [ApiAuthController::class, 'updatePassword']);

        // POS (Point of Sale)
        Route::get('/pos/items', [ApiPosController::class, 'items']);
        Route::post('/pos/checkout', [ApiPosController::class, 'checkout']);
        Route::get('/pos/nota/{transaksi}', [ApiPosController::class, 'nota']);
        Route::get('/pos/struk/{transaksi}', [ApiPosController::class, 'struk']);

        // Dashboard Summary
        Route::get('/admin/dashboard', [ApiDashboardController::class, 'summary']);

        // Produk & Kategori Management
        Route::get('/admin/produk', [ApiProdukController::class, 'index']);
        Route::post('/admin/produk', [ApiProdukController::class, 'store']);
        Route::get('/admin/produk/{produk}', [ApiProdukController::class, 'show']);
        Route::put('/admin/produk/{produk}', [ApiProdukController::class, 'update']);
        Route::patch('/admin/produk/{produk}', [ApiProdukController::class, 'update']);
        Route::delete('/admin/produk/{produk}', [ApiProdukController::class, 'destroy']);

        Route::get('/admin/kategori', [ApiProdukController::class, 'kategoriIndex']);
        Route::post('/admin/kategori', [ApiProdukController::class, 'kategoriStore']);
        Route::put('/admin/kategori/{kategori}', [ApiProdukController::class, 'kategoriUpdate']);
        Route::patch('/admin/kategori/{kategori}', [ApiProdukController::class, 'kategoriUpdate']);
        Route::delete('/admin/kategori/{kategori}', [ApiProdukController::class, 'kategoriDestroy']);

        // Service & Perangkat Management
        Route::get('/admin/services', [ApiServiceManagementController::class, 'index']);
        Route::post('/admin/services', [ApiServiceManagementController::class, 'store']);
        Route::get('/admin/services/{service}', [ApiServiceManagementController::class, 'show']);
        Route::put('/admin/services/{service}', [ApiServiceManagementController::class, 'update']);
        Route::patch('/admin/services/{service}', [ApiServiceManagementController::class, 'update']);
        Route::post('/admin/services/{service}/status', [ApiServiceManagementController::class, 'updateStatus']);
        Route::post('/admin/services/{service}/parts', [ApiServiceManagementController::class, 'storePart']);
        Route::delete('/admin/services/{service}/parts/{part}', [ApiServiceManagementController::class, 'destroyPart']);

        Route::get('/admin/perangkat', [ApiServiceManagementController::class, 'perangkatIndex']);
        Route::post('/admin/perangkat', [ApiServiceManagementController::class, 'perangkatStore']);
        Route::get('/admin/perangkat/{perangkat}', [ApiServiceManagementController::class, 'perangkatShow']);
        Route::put('/admin/perangkat/{perangkat}', [ApiServiceManagementController::class, 'perangkatUpdate']);
        Route::patch('/admin/perangkat/{perangkat}', [ApiServiceManagementController::class, 'perangkatUpdate']);

        // Transaksi
        Route::get('/admin/transaksi', [ApiTransaksiController::class, 'index']);
        Route::get('/admin/transaksi/{transaksi}', [ApiTransaksiController::class, 'show']);

        // --- Owner-Only API Routes ---
        Route::middleware('owner.api')->group(function (): void {
            // Analytics & Void
            Route::get('/admin/analytics', [ApiDashboardController::class, 'analytics']);
            Route::post('/admin/transaksi/{transaksi}/void', [ApiTransaksiController::class, 'void']);

            // Promo Management
            Route::get('/admin/promos', [ApiPromoManagementController::class, 'index']);
            Route::post('/admin/promos', [ApiPromoManagementController::class, 'store']);
            Route::get('/admin/promos/{promo}', [ApiPromoManagementController::class, 'show']);
            Route::put('/admin/promos/{promo}', [ApiPromoManagementController::class, 'update']);
            Route::patch('/admin/promos/{promo}', [ApiPromoManagementController::class, 'update']);
            Route::delete('/admin/promos/{promo}', [ApiPromoManagementController::class, 'destroy']);
            Route::post('/admin/promos/{promo}/toggle', [ApiPromoManagementController::class, 'toggle']);

            // Pegawai Management
            Route::get('/admin/pegawai', [ApiPegawaiController::class, 'index']);
            Route::post('/admin/pegawai', [ApiPegawaiController::class, 'store']);
            Route::get('/admin/pegawai/{pegawai}', [ApiPegawaiController::class, 'show']);
            Route::put('/admin/pegawai/{pegawai}', [ApiPegawaiController::class, 'update']);
            Route::patch('/admin/pegawai/{pegawai}', [ApiPegawaiController::class, 'update']);
            Route::delete('/admin/pegawai/{pegawai}', [ApiPegawaiController::class, 'destroy']);
            Route::post('/admin/pegawai/{pegawai}/revoke-sessions', [ApiPegawaiController::class, 'revokeSessions']);
        });
    });
});
