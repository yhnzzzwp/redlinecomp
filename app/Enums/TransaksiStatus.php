<?php

declare(strict_types=1);

namespace App\Enums;

enum TransaksiStatus: string
{
    case Normal = 'Normal';
    case Void = 'Void';
    case Refund = 'Refund';
}
