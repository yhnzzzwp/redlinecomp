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
        $prefix = (string) config('redline.prefix_resi');

        do {
            $random = strtoupper(\Illuminate\Support\Str::random(6));
            $kode = sprintf('%s-%d-%s', $prefix, $tahun, $random);
        } while (Service::query()->where('nomor_resi', $kode)->exists());

        return $kode;
    }
}
