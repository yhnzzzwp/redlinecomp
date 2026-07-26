<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Produk;
use Illuminate\Support\Str;

final class ProductService
{
    public function create(array $data): Produk
    {
        if (empty(trim($data['sku'] ?? ''))) {
            $data['sku'] = $this->generateUniqueSku();
        }

        return Produk::query()->create($data);
    }

    public function update(Produk $produk, array $data): Produk
    {
        if (empty(trim($data['sku'] ?? ''))) {
            $data['sku'] = $produk->sku ?? $this->generateUniqueSku();
        }

        $produk->update($data);

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
