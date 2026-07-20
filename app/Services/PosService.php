<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CheckoutData;
use App\Enums\TipeItem;
use App\Exceptions\PembayaranKurangException;
use App\Exceptions\StokTidakCukupException;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

final class PosService
{
    public function __construct(
        private readonly PromoService $promoService,
        private readonly KodeGenerator $kodeGenerator,
    ) {}

    public function checkout(CheckoutData $data, Pegawai $kasir): Transaksi
    {
        return DB::transaction(function () use ($data, $kasir): Transaksi {
            $subtotal = 0;
            $baris = [];

            foreach ($data->items as $item) {
                $produk = Produk::query()->lockForUpdate()->findOrFail($item->produkId);

                if ($produk->jumlah_produk < $item->jumlah) {
                    throw new StokTidakCukupException($produk->nama_produk, $produk->jumlah_produk);
                }

                $harga = (int) $produk->harga;
                $sub = $harga * $item->jumlah;
                $subtotal += $sub;
                $baris[] = [$produk, $item->jumlah, $harga, $sub];
            }

            $promo = $data->kodePromo !== null
                ? $this->promoService->hitung($data->kodePromo, $subtotal)
                : null;

            $diskon = $promo !== null ? $promo->diskon : 0;
            $total = $subtotal - $diskon;

            if ($data->bayar < $total) {
                throw new PembayaranKurangException($total, $data->bayar);
            }

            $transaksi = Transaksi::query()->create([
                'kode_nota' => $this->kodeGenerator->nota(),
                'pegawai_id' => $kasir->id,
                'promo_id' => $promo?->promoId,
                'metode_bayar' => $data->metodeBayar->value,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'bayar' => $data->bayar,
                'kembalian' => $data->bayar - $total,
                'nama_pembeli' => $data->namaPembeli,
                'nomor_hp_pembeli' => $data->nomorHpPembeli,
            ]);

            foreach ($baris as [$produk, $jumlah, $harga, $sub]) {
                $transaksi->items()->create([
                    'tipe' => TipeItem::Produk,
                    'produk_id' => $produk->id,
                    'nama_item' => $produk->nama_produk,
                    'jumlah' => $jumlah,
                    'harga' => $harga,
                    'subtotal' => $sub,
                ]);

                $produk->decrement('jumlah_produk', $jumlah);
            }

            return $transaksi->load(['items', 'promo', 'pegawai']);
        });
    }
}
