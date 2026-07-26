<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\RateLimiter::for('web', function (Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Tolak Host header di luar tiga host portal (anti host-header injection;
        // pembentukan URL absolut — reset link, redirect — tak bisa diracuni).
        $middleware->trustHosts(at: fn (): array => [
            preg_quote((string) config('redline.hosts.public'), '#'),
            preg_quote((string) config('redline.hosts.staff'), '#'),
            preg_quote((string) config('redline.hosts.admin'), '#'),
        ], subdomains: false);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            // Ganti/reset password mengakhiri sesi di perangkat lain, dan cookie
            // "ingat perangkat" dengan password lama ditolak (melengkapi halaman
            // Sesi Aktif — lihat SesiController).
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            // Pegawai nonaktif langsung ter-logout, tak menunggu sesi kedaluwarsa.
            \App\Http\Middleware\PastikanMasihBekerja::class,
        ]);

        $middleware->alias([
            'owner' => \App\Http\Middleware\EnsureOwner::class,
            'portal' => \App\Http\Middleware\EnsurePortal::class,
        ]);

        // Cek host portal harus berjalan SEBELUM auth: rute internal yang
        // diakses dari host publik mesti 404 (tersembunyi), bukan redirect login.
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\EnsurePortal::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
