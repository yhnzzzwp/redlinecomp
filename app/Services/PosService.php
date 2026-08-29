<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CheckoutData;
use App\Enums\TipeItem;
use App\Exceptions\PembayaranKurangException;
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

            $consolidated = [];
            foreach ($data->items as $item) {
                $key = strtolower($item->tipe) . '_' . $item->itemId;
                if (! isset($consolidated[$key])) {
                    $consolidated[$key] = [
                        'itemId' => $item->itemId,
                        'tipe' => $item->tipe,
                        'jumlah' => $item->jumlah,
                        'harga' => $item->harga,
                    ];
                } else {
                    $consolidated[$key]['jumlah'] += $item->jumlah;
                }
            }

            foreach ($consolidated as $item) {
                $tipe = (string) $item['tipe'];
                $itemId = (int) $item['itemId'];
                $jumlah = (int) $item['jumlah'];
                $harga = (int) $item['harga'];

                if (strtolower($tipe) === 'produk') {
                    $produk = Produk::query()->lockForUpdate()->findOrFail($itemId);

                    $sub = $harga * $jumlah;
                    $subtotal += $sub;
                    $baris[] = ['tipe' => TipeItem::Produk, 'model' => $produk, 'nama' => (string) $produk->nama_produk, 'jumlah' => $jumlah, 'harga' => $harga, 'sub' => $sub];
                } else {
                    $service = \App\Models\Service::query()->lockForUpdate()->findOrFail($itemId);

                    // Harga servis TIDAK boleh datang dari klien: server punya
                    // angka yang sah (biaya_service + seluruh part). Sebelumnya
                    // kasir bisa menutup servis Rp 2 juta dengan harga Rp 0 dan
                    // tetap menandai unitnya sudah diambil.
                    //
                    // Berbeda dengan produk: kolom harga produk sudah dihapus
                    // dari skema (migrasi 2026_08_20_000003), jadi harga produk
                    // memang diisi kasir. Servis punya harga di server.
                    $harga = $service->totalBiaya();
                    $sub = $harga * $jumlah;
                    $subtotal += $sub;
                    // Nama baris dihitung di sini, selagi tipenya masih Service.
                    // Sebelumnya memakai $b['model']->nama_barang — properti yang
                    // tidak ada pada model Service, sehingga baris servis pada
                    // nota tersimpan sebagai "Servis: " tanpa nama sama sekali.
                    // perangkat_id nullable (nullOnDelete), jadi relasinya memang
                    // bisa kosong. Ditulis eksplisit agar benar sekaligus terbaca
                    // jelas oleh analisis statis.
                    $perangkat = $service->perangkat;
                    $namaServis = 'Servis: '.($perangkat !== null
                        ? $perangkat->merk_model
                        : 'Unit #'.$service->nomor_resi);

                    $baris[] = ['tipe' => TipeItem::Servis, 'model' => $service, 'nama' => $namaServis, 'jumlah' => $jumlah, 'harga' => $harga, 'sub' => $sub];
                }
            }

            $promo = $data->kodePromo !== null
                ? $this->promoService->hitung($data->kodePromo, $subtotal, kunci: true)
                : null;

            $diskon = $promo !== null ? $promo->diskon : 0;
            $total = $subtotal - $diskon;

            if ($data->bayar < $total) {
                throw new PembayaranKurangException($total, $data->bayar);
            }

            $transaksi = \App\Support\CobaUlang::unik(fn (): Transaksi => Transaksi::query()->create([
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
                // Kolom status punya default 'Normal' di database, tetapi model
                // hasil create() tidak memuat nilai default itu — atributnya
                // null di memori. Respons API lalu membaca $t->status->value
                // pada null; sebelumnya tertutup fallback "?? (string) $t->status"
                // sehingga field status pada nota API selalu string kosong.
                // Disamakan dengan jalur sync yang memang menyetelnya eksplisit.
                'status' => \App\Enums\TransaksiStatus::Normal,
            ]));

            foreach ($baris as $b) {
                $transaksi->items()->create([
                    'tipe' => $b['tipe'],
                    'produk_id' => $b['tipe'] === TipeItem::Produk ? $b['model']->id : null,
                    'service_id' => $b['tipe'] === TipeItem::Servis ? $b['model']->id : null,
                    'nama_item' => $b['nama'],
                    'jumlah' => $b['jumlah'],
                    'harga' => $b['harga'],
                    'subtotal' => $b['sub'],
                ]);

                if ($b['tipe'] === TipeItem::Produk) {

                } else {

                    $sebelumnya = $b['model']->status;

                    // Guard transisi status ada di StatusService::canTransitionTo
                    // dan ditegakkan ServiceTicketService, tetapi jalur POS ini
                    // melewatinya: unit yang baru diterima dan belum dikerjakan
                    // bisa langsung dilompatkan ke "Sudah Diambil".
                    if ($sebelumnya !== \App\Enums\StatusService::SudahDiambil
                        && ! $sebelumnya->canTransitionTo(\App\Enums\StatusService::SudahDiambil)) {
                        throw new \App\Exceptions\ServisBelumSelesaiException(
                            (string) $b['model']->nomor_resi,
                            $sebelumnya->value
                        );
                    }

                    $b['model']->update(['status' => \App\Enums\StatusService::SudahDiambil]);
                    if ($sebelumnya !== \App\Enums\StatusService::SudahDiambil) {
                        $b['model']->riwayat()->create([
                            'pegawai_id' => $kasir->id,
                            'status' => \App\Enums\StatusService::SudahDiambil,
                            'catatan' => 'Dibayar & diambil via POS — Nota #' . $transaksi->kode_nota,
                        ]);
                    }
                }
            }

            if ($promo !== null) {
                \App\Models\Promo::where('id', $promo->promoId)->increment('terpakai');
            }

            return $transaksi->load(['items', 'promo', 'pegawai']);
        });
    }
}
