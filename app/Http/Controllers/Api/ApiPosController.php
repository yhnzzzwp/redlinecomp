<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MetodeBayar;
use App\Enums\TipeItem;
use App\Enums\TransaksiStatus;
use App\Http\Controllers\Controller;
use App\Models\ItemTransaksi;
use App\Models\Promo;
use App\Models\Transaksi;
use App\Services\KodeGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApiPosController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $payload = $request->all();
        $transactions = isset($payload['transaksi']) && is_array($payload['transaksi'])
            ? $payload['transaksi']
            : [$payload];

        $synced = [];
        $errors = [];

        foreach ($transactions as $idx => $trxData) {
            $validator = Validator::make($trxData, [
                'local_id' => ['required', 'string', 'max:255'],
                'pegawai_id' => ['nullable', 'exists:pegawai,id'],
                'kode_promo' => ['nullable', 'string', 'exists:promo,kode_promo'],
                'metode_bayar' => ['required', 'string'],
                'nama_pembeli' => ['nullable', 'string', 'max:255'],
                'nomor_hp_pembeli' => ['nullable', 'string', 'max:30'],
                'bayar' => ['required', 'integer', 'min:0'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.tipe' => ['required', 'string'],
                'items.*.produk_id' => ['nullable', 'exists:produk,id'],
                'items.*.service_id' => ['nullable', 'exists:service,id'],
                'items.*.nama_item' => ['required', 'string', 'max:255'],
                'items.*.jumlah' => ['required', 'integer', 'min:1'],
                'items.*.harga' => ['required', 'integer', 'min:0'],
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'index' => $idx,
                    'local_id' => $trxData['local_id'] ?? null,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            $existing = Transaksi::where('local_id', $trxData['local_id'])->first();
            if ($existing) {
                $synced[] = [
                    'local_id' => $existing->local_id,
                    'kode_nota' => $existing->kode_nota,
                    'status' => 'already_synced',
                ];
                continue;
            }

            try {
                $record = DB::transaction(function () use ($trxData) {
                    $subtotal = 0;
                    foreach ($trxData['items'] as $item) {
                        $subtotal += ((int) $item['harga']) * ((int) $item['jumlah']);
                    }

                    $diskon = 0;
                    $promoId = null;
                    if (! empty($trxData['kode_promo'])) {
                        $promo = Promo::where('kode_promo', $trxData['kode_promo'])->first();
                        if ($promo && $promo->bisaDigunakan($subtotal)) {
                            $diskon = $promo->hitungDiskon($subtotal);
                            $promoId = $promo->id;
                            $promo->increment('terpakai');
                        }
                    }

                    $total = max(0, $subtotal - $diskon);
                    $bayar = (int) $trxData['bayar'];
                    $kembalian = max(0, $bayar - $total);

                    $transaksi = Transaksi::create([
                        'kode_nota' => (new KodeGenerator())->nota(),
                        'local_id' => $trxData['local_id'],
                        'pegawai_id' => $trxData['pegawai_id'] ?? null,
                        'promo_id' => $promoId,
                        'metode_bayar' => MetodeBayar::tryFrom($trxData['metode_bayar']) ?? MetodeBayar::Tunai,
                        'subtotal' => $subtotal,
                        'diskon' => $diskon,
                        'total' => $total,
                        'bayar' => $bayar,
                        'kembalian' => $kembalian,
                        'nama_pembeli' => $trxData['nama_pembeli'] ?? 'Umum',
                        'nomor_hp_pembeli' => $trxData['nomor_hp_pembeli'] ?? null,
                        'status' => TransaksiStatus::Selesai,
                    ]);

                    foreach ($trxData['items'] as $item) {
                        ItemTransaksi::create([
                            'transaksi_id' => $transaksi->id,
                            'tipe' => TipeItem::tryFrom($item['tipe']) ?? TipeItem::Produk,
                            'produk_id' => $item['produk_id'] ?? null,
                            'service_id' => $item['service_id'] ?? null,
                            'nama_item' => $item['nama_item'],
                            'jumlah' => (int) $item['jumlah'],
                            'harga' => (int) $item['harga'],
                            'subtotal' => ((int) $item['harga']) * ((int) $item['jumlah']),
                        ]);
                    }

                    return $transaksi;
                });

                $synced[] = [
                    'local_id' => $record->local_id,
                    'kode_nota' => $record->kode_nota,
                    'status' => 'synced',
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'index' => $idx,
                    'local_id' => $trxData['local_id'],
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        return response()->json([
            'status' => count($errors) === 0 ? 'success' : 'partial',
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }
}
