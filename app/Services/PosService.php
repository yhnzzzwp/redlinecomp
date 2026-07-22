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

            // Aggregated item lines by tipe & itemId to prevent duplicate line double-spending
            $consolidated = [];
            foreach ($data->items as $item) {
                $key = strtolower($item->tipe) . '_' . $item->itemId;
                if (! isset($consolidated[$key])) {
                    $consolidated[$key] = [
                        'itemId' => $item->itemId,
                        'tipe' => $item->tipe,
                        'jumlah' => $item->jumlah,
                    ];
                } else {
                    $consolidated[$key]['jumlah'] += $item->jumlah;
                }
            }

            foreach ($consolidated as $item) {
                $tipe = (string) $item['tipe'];
                $itemId = (int) $item['itemId'];
                $jumlah = (int) $item['jumlah'];

                if (strtolower($tipe) === 'produk') {
                    $produk = Produk::query()->lockForUpdate()->findOrFail($itemId);

                    if ($produk->jumlah_produk < $jumlah) {
                        throw new StokTidakCukupException($produk->nama_produk, $produk->jumlah_produk);
                    }

                    $harga = (int) $produk->harga;
                    $sub = $harga * $jumlah;
                    $subtotal += $sub;
                    $baris[] = ['tipe' => TipeItem::Produk, 'model' => $produk, 'jumlah' => $jumlah, 'harga' => $harga, 'sub' => $sub];
                } else {
                    $service = \App\Models\Service::query()->lockForUpdate()->findOrFail($itemId);
                    $harga = (int) $service->biaya_service;
                    $sub = $harga * $jumlah;
                    $subtotal += $sub;
                    $baris[] = ['tipe' => TipeItem::Servis, 'model' => $service, 'jumlah' => $jumlah, 'harga' => $harga, 'sub' => $sub];
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

            if ($promo !== null) {
                \App\Models\Promo::where('id', $promo->promoId)->increment('terpakai');
            }

            return $transaksi->load(['items', 'promo', 'pegawai']);
        });
    }
}
