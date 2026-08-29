<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalisasi nomor resi servis.
 *
 * Resi dicetak di nota sebagai "PK-2026-0001", tetapi pelanggan mengetiknya
 * ulang dari kertas: dengan spasi, tanpa tanda hubung, huruf kecil, kadang
 * memakai garis bawah. Pencocokan persis membuat semua variasi itu dianggap
 * "tiket tidak ditemukan" — kegagalan yang terlihat seperti data hilang,
 * padahal hanya soal pemisah.
 */
final class Resi
{
    /**
     * Bentuk kanonik: huruf besar tanpa pemisah apa pun.
     *
     * "pk 2026 00 01" dan "PK-2026-0001" sama-sama menjadi "PK20260001".
     */
    public static function kanonik(string $resi): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $resi));
    }
}
