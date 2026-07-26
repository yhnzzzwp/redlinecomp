<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class ImporProdukException extends Exception
{
    /** @param list<string> $barisGagal daftar galat per baris, siap tampil */
    public function __construct(public readonly array $barisGagal)
    {
        parent::__construct('Impor produk dibatalkan: ' . count($barisGagal) . ' masalah ditemukan.');
    }
}
