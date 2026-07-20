<?php

declare(strict_types=1);

namespace App\Data;

final readonly class PromoResult
{
    public function __construct(
        public int $promoId,
        public int $diskon,
    ) {}
}
