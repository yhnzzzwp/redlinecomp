<?php

declare(strict_types=1);

namespace App\Data;

final readonly class CartLine
{
    public function __construct(
        public int $produkId,
        public int $jumlah,
    ) {}
}
