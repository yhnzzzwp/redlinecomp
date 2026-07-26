<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Format mata uang terpusat — satu-satunya sumber format "Rp 1.234.567".
 * Dipakai view lewat `$rp = Uang::rupiah(...)` (first-class callable).
 */
final class Uang
{
    public static function rupiah(int|float|string|null $nilai): string
    {
        return 'Rp ' . number_format((int) $nilai, 0, ',', '.');
    }
}
