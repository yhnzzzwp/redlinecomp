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

    /**
     * Status yang diizinkan dari status saat ini.
     *
     * Aturan:
     * - Diterima → Dikerjakan
     * - Dikerjakan → MenungguSparepart, Selesai
     * - MenungguSparepart → Dikerjakan (mundur), Selesai (lompat)
     * - Selesai → SudahDiambil (tidak bisa mundur)
     * - SudahDiambil → (final, tidak bisa berubah)
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Diterima => [self::Dikerjakan],
            self::Dikerjakan => [self::MenungguSparepart, self::Selesai],
            self::MenungguSparepart => [self::Dikerjakan, self::Selesai],
            self::Selesai => [self::SudahDiambil],
            self::SudahDiambil => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
