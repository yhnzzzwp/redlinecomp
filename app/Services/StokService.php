<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipeMutasiStok;
use App\Models\MutasiStok;
use App\Models\Produk;

/**
 * Pencatat mutasi stok — satu pintu untuk semua jejak pergerakan barang.
 * Perubahan tanpa selisih tidak dicatat (bebas derau).
 */
final class StokService
{
    public function catat(
        Produk $produk,
        int $sebelum,
        int $sesudah,
        TipeMutasiStok $tipe,
        ?string $keterangan = null,
        ?int $pegawaiId = null,
    ): void {
        if ($sebelum === $sesudah) {
            return;
        }

        MutasiStok::query()->create([
            'produk_id' => $produk->id,
            'tipe' => $tipe->value,
            'jumlah_sebelum' => $sebelum,
            'selisih' => $sesudah - $sebelum,
            'jumlah_sesudah' => $sesudah,
            'keterangan' => $keterangan,
            'pegawai_id' => $pegawaiId ?? auth()->id(),
        ]);
    }
}
