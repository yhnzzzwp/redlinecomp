<?php

declare(strict_types=1);

namespace App\Data;

final readonly class HasilImporProduk
{
    public function __construct(
        public int $baru,
        public int $diperbarui,
        public int $kategoriBaru,
    ) {}

    public function ringkasan(): string
    {
        $pesan = "Impor Excel berhasil: {$this->baru} produk baru, {$this->diperbarui} produk diperbarui";
        if ($this->kategoriBaru > 0) {
            $pesan .= ", {$this->kategoriBaru} kategori baru dibuat";
        }

        return $pesan . '.';
    }
}
