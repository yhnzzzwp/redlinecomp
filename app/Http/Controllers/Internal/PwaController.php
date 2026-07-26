<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Support\Portal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manifest PWA portal internal — supaya POS bisa di-install di HP/tablet
 * kasir (Add to Home Screen, tampil standalone).
 *
 * Disajikan lewat route di grup portal:internal, BUKAN file statis, supaya:
 *  - dari host publik manifest ini 404 (keberadaan portal tetap tersembunyi);
 *  - nama aplikasi mengikuti portal (karyawan vs admin).
 *
 * Sengaja tanpa middleware auth: browser mengambil manifest tanpa membawa
 * cookie sesi, sehingga bila diproteksi login pemasangan PWA akan gagal.
 * Isinya tidak sensitif (nama + ikon). Service worker/offline belum dibuat
 * (keputusan sadar — CSP ketat, dievaluasi belakangan).
 */
final class PwaController extends Controller
{
    public function manifest(Request $request): JsonResponse
    {
        $portal = Portal::fromRequest($request);

        return response()->json([
            'id' => '/pos',
            'name' => 'SIRC POS · '.$portal->label(),
            'short_name' => 'SIRC POS',
            'description' => 'Kasir Redline Komputer — transaksi, nota, dan struk thermal.',
            'lang' => 'id',
            'start_url' => '/pos',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#f3f4f6', // token --bg (tema terang)
            'theme_color' => '#ffffff',      // token --surface (topbar)
            'icons' => [
                ['src' => '/icons/pwa-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/pwa-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => '/icons/pwa-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES);
    }
}
