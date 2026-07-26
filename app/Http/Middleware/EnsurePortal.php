<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Portal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memisahkan zona aplikasi per subdomain:
 *
 *  - portal:public   → hanya dilayani host publik. Diakses lewat host
 *                      admin/karyawan → dialihkan ke login portal tsb.
 *  - portal:internal → hanya ada di host admin/karyawan. Dari host publik
 *                      keberadaannya disembunyikan (404, bukan 403).
 *                      Role user yang login wajib cocok dengan portalnya
 *                      (Owner ↔ admin, Karyawan ↔ karyawan) — pertahanan
 *                      kedua setelah pembatasan saat login.
 */
class EnsurePortal
{
    public function handle(Request $request, Closure $next, string $zone): Response
    {
        $portal = Portal::fromRequest($request);

        if ($zone === 'internal') {
            if ($portal === Portal::Publik) {
                abort(404);
            }

            $user = $request->user();
            if ($user && $user->role !== $portal->expectedRole()) {
                abort(403, 'Akun Anda tidak terdaftar pada portal ini.');
            }
        }

        if ($zone === 'public' && $portal !== Portal::Publik) {
            return redirect()->route($request->user() ? 'dashboard' : 'login');
        }

        return $next($request);
    }
}
