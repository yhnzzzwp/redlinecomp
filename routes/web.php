<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\PosController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.landing')->name('landing');

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

    Route::middleware('owner')->group(function () {

    });
});
