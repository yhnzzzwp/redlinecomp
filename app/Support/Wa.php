<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusService;
use App\Models\Service;

/**
 * Notifikasi WhatsApp via tautan wa.me — tanpa API pihak ketiga (sesuai
 * desain nol-vendor situs ini). Kasir menekan tombol, WhatsApp terbuka
 * dengan pesan yang sudah terisi sesuai status servis terkini.
 */
final class Wa
{
    /** "0856..." / "+62 856-..." → "62856..."; null bila tak ada digit. */
    public static function normalisasi(?string $nomor): ?string
    {
        $digit = preg_replace('/[^0-9]/', '', (string) $nomor);
        if ($digit === '' ) {
            return null;
        }

        if (str_starts_with($digit, '0')) {
            $digit = '62' . substr($digit, 1);
        }

        return $digit;
    }

    /** Tautan wa.me berisi pesan status terkini; null bila customer tanpa nomor HP. */
    public static function linkStatusServis(Service $servis): ?string
    {
        $nomor = self::normalisasi($servis->nomor_hp_customer);
        if ($nomor === null) {
            return null;
        }

        return 'https://wa.me/' . $nomor . '?text=' . rawurlencode(self::pesanStatus($servis));
    }

    public static function pesanStatus(Service $servis): string
    {
        $toko = (string) config('redline.store_name');
        $lacak = rtrim((string) config('app.url'), '/') . '/cek-servis?resi=' . rawurlencode($servis->nomor_resi);

        $inti = match ($servis->status) {
            StatusService::Diterima => "unit Anda sudah kami terima dan tercatat dalam antrean servis.",
            StatusService::Dikerjakan => "unit Anda sedang dikerjakan teknisi kami.",
            StatusService::MenungguSparepart => "pengerjaan unit Anda sementara menunggu ketersediaan sparepart. Kami kabari lagi begitu part tiba.",
            StatusService::Selesai => "servis unit Anda telah SELESAI. Total biaya: " . Uang::rupiah($servis->totalBiaya()) . ". Silakan diambil di toko dengan membawa nota/resi.",
            StatusService::SudahDiambil => "unit Anda telah diambil. Terima kasih telah mempercayakan servis kepada kami!",
        };

        return "Halo {$servis->nama_customer}, dari *{$toko}*.\n\n"
            . "Servis *{$servis->nama_barang}* (resi {$servis->nomor_resi}): {$inti}\n\n"
            . "Pantau status kapan saja: {$lacak}";
    }
}
