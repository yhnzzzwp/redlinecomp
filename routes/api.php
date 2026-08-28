<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiKatalogController;
use App\Http\Controllers\Api\ApiPosController;
use App\Http\Controllers\Api\ApiPromoController;
use App\Http\Controllers\Api\ApiServiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', [ApiAuthController::class, 'health']);
    Route::post('/auth/login', [ApiAuthController::class, 'login']);

    Route::get('/katalog', [ApiKatalogController::class, 'index']);
    Route::get('/katalog/{produk}', [ApiKatalogController::class, 'show']);
    Route::get('/kategori', [ApiKatalogController::class, 'kategori']);

    Route::get('/promo', [ApiPromoController::class, 'index']);

    Route::get('/service/cek', [ApiServiceController::class, 'cek']);
    Route::get('/perangkat/{kode}', [ApiServiceController::class, 'perangkat']);

    Route::post('/pos/sync', [ApiPosController::class, 'sync']);
    Route::post('/pos/checkout', [ApiPosController::class, 'sync']);
});
