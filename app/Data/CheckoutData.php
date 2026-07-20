<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\MetodeBayar;

final readonly class CheckoutData
{
    /** @param list<CartLine> $items */
    public function __construct(
        public array $items,
        public MetodeBayar $metodeBayar,
        public int $bayar,
        public ?string $kodePromo = null,
        public ?string $namaPembeli = null,
        public ?string $nomorHpPembeli = null,
    ) {}
}
