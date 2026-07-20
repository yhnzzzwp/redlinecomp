<?php

declare(strict_types=1);

namespace App\Exceptions;

final class PromoTidakValidException extends PosException
{
    public function __construct(string $kode, string $alasan = 'Kode promo tidak valid atau kedaluwarsa.')
    {
        parent::__construct("Promo {$kode}: {$alasan}");
    }
}
