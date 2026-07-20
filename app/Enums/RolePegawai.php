<?php

declare(strict_types=1);

namespace App\Enums;

enum RolePegawai: string
{
    case Karyawan = 'Karyawan';
    case Owner = 'Owner';

    public function label(): string
    {
        return $this->value;
    }
}
