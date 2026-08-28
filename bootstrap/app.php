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
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustHosts(at: fn (): array => [
            preg_quote((string) config('redline.hosts.public'), '#'),
            preg_quote((string) config('redline.hosts.staff'), '#'),
            preg_quote((string) config('redline.hosts.admin'), '#'),
            '.*',
        ], subdomains: false);

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

