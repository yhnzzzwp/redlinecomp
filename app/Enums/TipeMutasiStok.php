<?php

declare(strict_types=1);

namespace App\Enums;

enum TipeMutasiStok: string
{
    case Penjualan = 'Penjualan';
    case Void = 'Void';
    case Penyesuaian = 'Penyesuaian';
    case Opname = 'Opname';
    case Impor = 'Impor';

    public function warna(): string
    {
        return match ($this) {
            self::Penjualan => 'red',
            self::Void => 'amber',
            self::Penyesuaian => 'blue',
            self::Opname => 'green',
            self::Impor => 'gray',
        };
    }
}
