<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Models\Transaksi;

final class KodeGenerator
{
    public function nota(): string
    {
        do {
            $kode = (string) random_int(100000, 999999);
        } while (Transaksi::query()->where('kode_nota', $kode)->exists());

        return $kode;
    }

    public function resi(): string
    {
        $tahun = (int) now()->year;
        $urutan = Service::query()->whereYear('created_at', $tahun)->count() + 1;

        return sprintf('%s-%d-%04d', (string) config('redline.prefix_resi'), $tahun, $urutan);
    }
}
