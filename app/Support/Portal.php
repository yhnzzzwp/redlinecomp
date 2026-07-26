<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RolePegawai;
use Illuminate\Http\Request;

/**
 * Peta host → portal. Sumber kebenaran tunggal untuk pemisahan zona
 * berbasis subdomain: publik (customer), karyawan, dan admin (owner).
 *
 * Host tiap portal diatur lewat config/redline.php ('hosts'). Cookie sesi
 * bersifat host-only (SESSION_DOMAIN=null) sehingga sesi login admin,
 * karyawan, dan publik terisolasi satu sama lain.
 */
enum Portal: string
{
    case Publik = 'public';
    case Staff = 'staff';
    case Admin = 'admin';

    public static function fromRequest(Request $request): self
    {
        $host = strtolower($request->getHost());

        return match ($host) {
            self::Admin->host() => self::Admin,
            self::Staff->host() => self::Staff,
            default => self::Publik,
        };
    }

    public function host(): string
    {
        $host = (string) config('redline.hosts.'.$this->value, 'localhost');

        // Buang port bila tak sengaja ikut tertulis di env/config.
        return strtolower(strstr($host, ':', true) ?: $host);
    }

    /** Role yang diizinkan login pada portal ini (null = tanpa login). */
    public function expectedRole(): ?RolePegawai
    {
        return match ($this) {
            self::Publik => null,
            self::Staff => RolePegawai::Karyawan,
            self::Admin => RolePegawai::Owner,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Publik => 'Redline Komputer',
            self::Staff => 'Portal Karyawan',
            self::Admin => 'Admin Console',
        };
    }
}
