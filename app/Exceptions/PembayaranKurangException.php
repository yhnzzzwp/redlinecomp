<?php

declare(strict_types=1);

namespace App\Exceptions;

final class PembayaranKurangException extends PosException
{
    public function __construct(int $total, int $bayar)
    {
        parent::__construct("Pembayaran kurang. Total {$total}, dibayar {$bayar}.");
    }
}
