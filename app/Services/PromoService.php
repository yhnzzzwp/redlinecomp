<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PromoResult;
use App\Enums\TipePromo;
use App\Exceptions\PromoTidakValidException;
use App\Models\Promo;

final class PromoService
{
    public function hitung(string $kode, int $subtotal): PromoResult
    {
        $promo = Promo::query()->where('kode_promo', $kode)->first();

        if ($promo === null || ! $promo->sedangBerlaku()) {
            throw new PromoTidakValidException($kode);
        }

        if ($subtotal < (int) $promo->minimal_transaksi) {
            throw new PromoTidakValidException($kode, 'Minimal transaksi belum terpenuhi.');
        }

        $diskon = match ($promo->tipe_promo) {
            TipePromo::Persen => intdiv($subtotal * (int) $promo->besar_promo, 100),
            TipePromo::Nominal => (int) $promo->besar_promo,
        };

        if ($promo->maksimal_diskon !== null) {
            $diskon = min($diskon, (int) $promo->maksimal_diskon);
        }

        return new PromoResult($promo->id, min($diskon, $subtotal));
    }
}
