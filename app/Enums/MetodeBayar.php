<?php

declare(strict_types=1);

namespace App\Enums;

enum MetodeBayar: string
{
    case Tunai = 'Tunai';
    case Transfer = 'Transfer';
    case QRIS = 'QRIS';
}
