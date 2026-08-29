<?php

namespace Tests;

use App\Support\Portal;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * Arahkan pembuatan URL (route()) ke host portal tertentu sehingga
     * request test mendarat di subdomain yang benar: 'public' | 'staff' | 'admin'.
     */
    /**
     * Data untuk ServiceTicketService::buat() sesuai skema yang berlaku.
     *
     * Migrasi 2026_08_20_000002 memindahkan identitas pelanggan dan perangkat
     * ke tabel `perangkat`; buat() kini menuntut perangkat_id + keluhan,
     * sementara tes lama masih mengoper nama_customer/nama_barang/masalah.
     *
     * @return array<string, mixed>
     */
    protected function dataServis(
        string $namaCustomer = 'Budi',
        string $merkModel = 'Laptop Asus',
        string $keluhan = 'Mati total',
    ): array {
        $perangkat = \App\Models\Perangkat::create([
            'kode_perangkat' => 'PK-UJI-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
            'nama_customer' => $namaCustomer,
            'nomor_hp_customer' => '081200000000',
            'merk_model' => $merkModel,
        ]);

        return [
            'perangkat_id' => $perangkat->id,
            'keluhan' => $keluhan,
        ];
    }

    protected function usePortal(string $portal): void
    {
        $root = 'http://'.Portal::from($portal)->host();

        config(['app.url' => $root]);
        URL::forceRootUrl($root);
    }
}
