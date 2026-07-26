<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nonce harus dibuat sebelum view dirender supaya tag Vite ikut membawanya.
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // Subdomain portal (admin/karyawan) tidak boleh diindeks mesin pencari.
        if (\App\Support\Portal::fromRequest($request) !== \App\Support\Portal::Publik) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        // HSTS hanya relevan (dan hanya dihormati browser) di atas HTTPS.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $nonce = Vite::cspNonce();

        // 'unsafe-eval' dibutuhkan Alpine.js (ekspresi x-data/@click dievaluasi
        // lewat Function constructor). 'unsafe-inline' pada style karena Bootstrap
        // dan sejumlah atribut style= di blade.
        $script = ["'self'", "'nonce-{$nonce}'", "'unsafe-eval'"];
        $style = ["'self'", "'unsafe-inline'"];
        $connect = ["'self'"];

        // Vite dev server saat `npm run dev`.
        if (! app()->isProduction()) {
            $devServer = 'http://localhost:5173';
            $script[] = $devServer;
            $style[] = $devServer;
            $connect[] = $devServer;
            $connect[] = 'ws://localhost:5173';
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            'connect-src '.implode(' ', $connect),
            "img-src 'self' data:",
            "font-src 'self' data:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        return implode('; ', $directives);
    }
}
