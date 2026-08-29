<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\PromoResult;
use App\Enums\TipePromo;
use App\Exceptions\PromoTidakValidException;
use App\Models\Promo;

final class PromoService
{
    /**
     * @param  bool  $kunci  Kunci baris promo (lockForUpdate) selama transaksi
     *                       berjalan. WAJIB true pada jalur checkout: tanpa itu
     *                       pemeriksaan kuota dan penambahan `terpakai` terpisah
     *                       oleh jeda, sehingga dua checkout bersamaan pada sisa
     *                       kuota terakhir sama-sama lolos dan promo terpakai
     *                       melebihi kuotanya. Jalur cek publik tidak perlu
     *                       mengunci baris.
     */
    public function hitung(string $kode, int $subtotal, bool $kunci = false): PromoResult
    {
        $query = Promo::query()->where('kode_promo', $kode);

        if ($kunci) {
            $query->lockForUpdate();
        }

        $promo = $query->first();

        if ($promo === null || ! $promo->sedangBerlaku()) {
            throw new PromoTidakValidException($kode);
        }

        if (!$promo->masihAdaKuota()) {
            throw new PromoTidakValidException($kode, 'Kuota promo sudah habis.');
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
