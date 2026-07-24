<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Produk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ProductService
{
    public function create(array $data, ?UploadedFile $foto): Produk
    {
        if (empty(trim($data['sku'] ?? ''))) {
            $data['sku'] = $this->generateUniqueSku();
        }

        if ($foto !== null) {
            $data['foto_produk'] = $this->simpanFoto($foto);
        }

        return Produk::query()->create($data);
    }

    public function update(Produk $produk, array $data, ?UploadedFile $foto): Produk
    {
        if (empty(trim($data['sku'] ?? ''))) {
            $data['sku'] = $produk->sku ?? $this->generateUniqueSku();
        }

        if ($foto !== null) {
            $this->hapusFoto($produk->foto_produk);
            $data['foto_produk'] = $this->simpanFoto($foto);
        }

        $produk->update($data);

        return $produk;
    }

    public function delete(Produk $produk): void
    {
        $this->hapusFoto($produk->foto_produk);
        $produk->delete();
    }

    private function generateUniqueSku(): string
    {
        do {
            $sku = 'RL-PRD-' . strtoupper(Str::random(6));
        } while (Produk::query()->where('sku', $sku)->exists());

        return $sku;
    }

    private function simpanFoto(UploadedFile $foto): string
    {
        return $foto->store('produk', 'public');
    }

    private function hapusFoto(?string $path): void
    {
        if ($path !== null && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

