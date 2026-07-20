<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusService: string
{
    case Diterima = 'Diterima';
    case Dikerjakan = 'Dikerjakan';
    case MenungguSparepart = 'Menunggu Sparepart';
    case Selesai = 'Selesai';
    case SudahDiambil = 'Sudah Diambil';

    public function warna(): string
    {
        return match ($this) {
            self::Diterima => 'blue',
            self::Dikerjakan => 'amber',
            self::MenungguSparepart => 'red',
            self::Selesai => 'green',
            self::SudahDiambil => 'gray',
        };
    }

    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
