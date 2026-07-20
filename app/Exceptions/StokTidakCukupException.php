<?php

declare(strict_types=1);

namespace App\Exceptions;

final class StokTidakCukupException extends PosException
{
    public function __construct(string $namaProduk, int $sisa)
    {
        parent::__construct("Stok tidak mencukupi untuk {$namaProduk}. Sisa {$sisa} unit.");
    }
}
