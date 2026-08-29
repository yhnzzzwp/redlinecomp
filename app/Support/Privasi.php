<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Penyamaran data pribadi untuk permukaan PUBLIK.
 *
 * Endpoint pelacakan servis dan perangkat bisa diakses tanpa login — yang
 * menjaganya hanyalah kerahasiaan nomor resi / kode perangkat. Menampilkan
 * nama dan nomor telepon pelanggan secara utuh membuat siapa pun yang menebak
 * atau memindai kode bisa memanen daftar pelanggan berikut kontaknya.
 *
 * Tujuannya: cukup bagi pelanggan untuk MENGENALI datanya sendiri, tidak
 * cukup bagi orang lain untuk MENGUMPULKANNYA.
 */
final class Privasi
{
    /** "Budi Santoso" -> "Budi S." · "Andi" -> "Andi" */
    public static function namaSingkat(?string $nama): ?string
    {
        $nama = trim((string) $nama);

        if ($nama === '') {
            return null;
        }

        $bagian = preg_split('/\s+/', $nama) ?: [];
        $depan = array_shift($bagian);

        if ($bagian === []) {
            return $depan;
        }

        $akhir = (string) end($bagian);
        $inisial = mb_strtoupper(mb_substr($akhir, 0, 1));

        return $depan.' '.$inisial.'.';
    }

    /** "081234567890" -> "****7890" */
    public static function nomorHp(?string $nomor): ?string
    {
        $nomor = trim((string) $nomor);

        if ($nomor === '') {
            return null;
        }

        return '****'.mb_substr($nomor, -4);
    }
}
