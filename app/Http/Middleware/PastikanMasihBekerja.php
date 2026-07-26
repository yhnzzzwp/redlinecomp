<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pegawai yang dinonaktifkan (`masih_bekerja = false`) harus berhenti bekerja
 * SEKETIKA, bukan menunggu sesinya kedaluwarsa. Status hanya dicek saat
 * Auth::attempt (kredensial login), jadi tanpa middleware ini sesi yang sudah
 * berjalan tetap hidup sampai 30 menit idle — celah nyata saat pegawai keluar.
 */
final class PastikanMasihBekerja
{
    public function handle(Request $request, Closure $next): Response
    {
        $pegawai = $request->user();

        if ($pegawai !== null && ! $pegawai->masih_bekerja) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login' => 'Akun Anda sudah tidak aktif. Hubungi Owner.',
            ]);
        }

        return $next($request);
    }
}
