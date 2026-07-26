<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Pegawai;
use PragmaRX\Google2FA\Google2FA;

/** Pembungkus tipis TOTP (RFC 6238) — tanpa layanan pihak ketiga. */
final class Totp
{
    public static function buatSecret(): string
    {
        return (new Google2FA)->generateSecretKey(32);
    }

    public static function verifikasi(string $secret, string $kode): bool
    {
        // Jendela ±1 langkah (30 detik) menoleransi selisih jam perangkat.
        return (bool) (new Google2FA)->verifyKey($secret, $kode, 1);
    }

    public static function kodeSaatIni(string $secret): string
    {
        return (new Google2FA)->getCurrentOtp($secret);
    }

    /** Tautan otpauth:// — dibuka di HP langsung menambah akun di aplikasi authenticator. */
    public static function otpauthUri(Pegawai $pegawai, string $secret): string
    {
        return (new Google2FA)->getQRCodeUrl(
            (string) config('redline.store_name'),
            $pegawai->username,
            $secret,
        );
    }
}
