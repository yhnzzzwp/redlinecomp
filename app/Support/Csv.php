<?php

declare(strict_types=1);

namespace App\Support;

final class Csv
{
    /**
     * Netralkan injeksi formula spreadsheet.
     *
     * Excel, LibreOffice, dan Google Sheets memperlakukan sel yang diawali
     * '=', '+', '-', '@', tab, atau carriage return sebagai FORMULA, bukan teks.
     * Nilai seperti =cmd|'/c calc'!A1 akan dieksekusi saat Owner membuka file
     * ekspor. Teks yang berbahaya di sini bukan teori: nama_item pada ekspor
     * jurnal datang langsung dari body /pos/sync dan dari impor Excel produk,
     * jadi kasir (atau siapa pun yang menguasai perangkat kasir) bisa menanam
     * formula yang meledak di komputer Owner.
     *
     * Awalan kutip satu membuat aplikasi spreadsheet menampilkannya sebagai
     * teks biasa. Angka dibiarkan apa adanya supaya kolom numerik — termasuk
     * nilai negatif — tetap bisa dihitung.
     */
    public static function aman(mixed $nilai): mixed
    {
        if (is_int($nilai) || is_float($nilai) || is_null($nilai)) {
            return $nilai;
        }

        $teks = (string) $nilai;

        if ($teks === '' || is_numeric($teks)) {
            return $teks;
        }

        if (preg_match('/^[=+\-@\t\r]/', $teks) === 1) {
            return "'".$teks;
        }

        return $teks;
    }

    /**
     * Terapkan aman() ke seluruh kolom satu baris.
     *
     * @param  array<int, mixed>  $baris
     * @return array<int, mixed>
     */
    public static function baris(array $baris): array
    {
        return array_map(static fn (mixed $v): mixed => self::aman($v), $baris);
    }
}
