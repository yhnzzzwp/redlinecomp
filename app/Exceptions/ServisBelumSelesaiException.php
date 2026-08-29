<?php

declare(strict_types=1);

namespace App\Exceptions;

final class ServisBelumSelesaiException extends PosException
{
    public function __construct(string $nomorResi, string $statusSekarang)
    {
        parent::__construct(
            "Servis {$nomorResi} berstatus \"{$statusSekarang}\" — hanya servis yang sudah Selesai yang boleh ditandai diambil."
        );
    }
}
