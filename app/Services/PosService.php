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
                if ($item->tipe === 'produk') {
                    $produk = Produk::query()->lockForUpdate()->findOrFail($item->itemId);

                    if ($produk->jumlah_produk < $item->jumlah) {
                        throw new StokTidakCukupException($produk->nama_produk, $produk->jumlah_produk);
                    }

                    $harga = (int) $produk->harga;
                    $sub = $harga * $item->jumlah;
                    $subtotal += $sub;
                    $baris[] = ['tipe' => TipeItem::Produk, 'model' => $produk, 'jumlah' => $item->jumlah, 'harga' => $harga, 'sub' => $sub];
                } else {
                    $service = \App\Models\Service::query()->lockForUpdate()->findOrFail($item->itemId);
                    $harga = (int) $service->biaya_service;
                    $sub = $harga * $item->jumlah;
                    $subtotal += $sub;
                    $baris[] = ['tipe' => TipeItem::Servis, 'model' => $service, 'jumlah' => $item->jumlah, 'harga' => $harga, 'sub' => $sub];
                }
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

            foreach ($baris as $b) {
                $transaksi->items()->create([
                    'tipe' => $b['tipe'],
                    'produk_id' => $b['tipe'] === TipeItem::Produk ? $b['model']->id : null,
                    'service_id' => $b['tipe'] === TipeItem::Servis ? $b['model']->id : null,
                    'nama_item' => $b['tipe'] === TipeItem::Produk ? $b['model']->nama_produk : 'Servis: ' . $b['model']->nama_barang,
                    'jumlah' => $b['jumlah'],
                    'harga' => $b['harga'],
                    'subtotal' => $b['sub'],
                ]);

                if ($b['tipe'] === TipeItem::Produk) {
                    $b['model']->decrement('jumlah_produk', $b['jumlah']);
                } else {
                    $b['model']->update(['status' => \App\Enums\StatusService::SudahDiambil]);
                }
            }

            return $transaksi->load(['items', 'promo', 'pegawai']);
        });
    }
}
