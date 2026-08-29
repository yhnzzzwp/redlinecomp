<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\RateLimiter::for('web', function (Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->ip());
            });
            \Illuminate\Support\Facades\RateLimiter::for('api', function (Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->ip());
            });

            // Login API dibatasi terutama per-username, bukan per-IP: di balik
            // Cloudflare Tunnel semua permintaan bisa terlihat datang dari satu
            // IP, jadi kunci per-IP saja akan mengunci seluruh toko saat ada
            // yang menyerang. Kunci username membatasi brute force ke akun yang
            // disasar; batas IP hanya jaring pengaman volume.
            \Illuminate\Support\Facades\RateLimiter::for('login-api', function (Request $request) {
                $username = mb_strtolower(trim((string) $request->input('username')));

                return [
                    \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('login-api:user:'.$username),
                    \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('login-api:ip:'.$request->ip()),
                ];
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tanpa ini, limiter 'api' di atas hanya terdaftar dan TIDAK PERNAH
        // dipasang ke grup rute api — Laravel 13 hanya menambahkan
        // 'throttle:<limiter>' bila throttleApi() dipanggil. Akibatnya seluruh
        // /api/v1/* (termasuk /auth/login) tanpa batas laju sama sekali.
        $middleware->throttleApi();

        // cloudflared berjalan sebagai service di jaringan docker dan
        // meneruskan IP asli lewat X-Forwarded-For. Tanpa proxy tepercaya,
        // $request->ip() selalu berisi IP container tunnel sehingga semua
        // throttling menjadi global. Sengaja TIDAK memercayai
        // X-Forwarded-Host: pemisahan portal bergantung pada header Host.
        $middleware->trustProxies(
            at: ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Pola '.*' sebelumnya memercayai SETIAP header Host, yang meniadakan
        // guna trustHosts sekaligus melemahkan pemisahan portal (EnsurePortal
        // memutuskan portal dari Host). Wildcard kini hanya berlaku di
        // lingkungan lokal; host tambahan untuk produksi — misalnya nama domain
        // Cloudflare Tunnel — didaftarkan lewat REDLINE_EXTRA_HOSTS.
        $middleware->trustHosts(at: fn (): array => array_values(array_filter([
            preg_quote((string) config('redline.hosts.public'), '#'),
            preg_quote((string) config('redline.hosts.staff'), '#'),
            preg_quote((string) config('redline.hosts.admin'), '#'),
            ...array_map(
                static fn (string $h): string => preg_quote(trim($h), '#'),
                array_filter(explode(',', (string) env('REDLINE_EXTRA_HOSTS', '')))
            ),
            app()->environment('local', 'testing') ? '.*' : null,
        ])), subdomains: false);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\PastikanMasihBekerja::class,
        ]);

        $middleware->alias([
            'owner' => \App\Http\Middleware\EnsureOwner::class,
            'portal' => \App\Http\Middleware\EnsurePortal::class,
            'auth.api' => \App\Http\Middleware\EnsureApiAuthenticated::class,
            'owner.api' => \App\Http\Middleware\EnsureApiOwner::class,
        ]);

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

