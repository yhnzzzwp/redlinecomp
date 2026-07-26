<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Produk;
use Illuminate\Support\Str;

final class ProductService
{
    public function __construct(private readonly StokService $stok) {}

    public function create(array $data): Produk
    {
        if (empty(trim($data['sku'] ?? ''))) {
            $data['sku'] = $this->generateUniqueSku();
        }

        $produk = Produk::query()->create($data);

        $this->stok->catat($produk, 0, (int) $produk->jumlah_produk, \App\Enums\TipeMutasiStok::Penyesuaian, 'Stok awal produk baru');

        return $produk;
    }

    public function update(Produk $produk, array $data): Produk
    {
        if (empty(trim($data['sku'] ?? ''))) {
            $data['sku'] = $produk->sku ?? $this->generateUniqueSku();
        }

        $sebelum = (int) $produk->jumlah_produk;
        $produk->update($data);

        $this->stok->catat($produk, $sebelum, (int) $produk->jumlah_produk, \App\Enums\TipeMutasiStok::Penyesuaian, 'Edit produk');

        return $produk;
    }

    public function delete(Produk $produk): void
    {
        $produk->delete();
    }

    private function generateUniqueSku(): string
    {
        do {
            $sku = 'RL-PRD-' . strtoupper(Str::random(6));
        } while (Produk::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
