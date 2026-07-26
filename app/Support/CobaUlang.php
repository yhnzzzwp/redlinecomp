<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\UniqueConstraintViolationException;

final class CobaUlang
{
    /**
     * Jalankan $aksi dan ulangi saat terjadi tabrakan unique constraint —
     * dipakai untuk insert berkode acak (kode nota / nomor resi): dua checkout
     * bersamaan bisa lolos cek "exists" lalu bentrok di index unik. MySQL/SQLite
     * membatalkan per-statement, jadi aman diulang di dalam transaksi yang sama.
     *
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    public static function unik(callable $aksi, int $maksPercobaan = 3): mixed
    {
        $percobaan = 0;

        while (true) {
            try {
                return $aksi();
            } catch (UniqueConstraintViolationException $e) {
                if (++$percobaan >= $maksPercobaan) {
                    throw $e;
                }
            }
        }
    }
}
