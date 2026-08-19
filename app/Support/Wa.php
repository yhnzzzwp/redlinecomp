<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\StatusService;
use App\Models\Service;

final class Wa
{

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

    public static function linkStatusServis(Service $servis): ?string
    {
        $nomor = self::normalisasi($servis->perangkat->nomor_hp_customer);
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

        return "Halo {$servis->perangkat->nama_customer}, dari *{$toko}*.\n\n"
            . "Servis *{$servis->perangkat->merk_model}* (resi {$servis->nomor_resi}): {$inti}\n\n"
            . "Pantau status kapan saja: {$lacak}";
    }
}
