<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Label perangkat dari string User-Agent — heuristik ringkas tanpa
 * dependensi pihak ketiga (cukup untuk halaman Sesi Aktif; bukan
 * deteksi presisi).
 */
final class Perangkat
{
    public static function label(?string $userAgent): string
    {
        $ua = (string) $userAgent;
        if (trim($ua) === '') {
            return 'Perangkat tak dikenal';
        }

        // Urutan penting: UA Chrome memuat "Safari", UA Edge memuat "Chrome".
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser lain',
        };

        $os = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return $os !== null ? "{$browser} · {$os}" : $browser;
    }
}
