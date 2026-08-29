<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\MetodeBayar;
use App\Enums\TransaksiStatus;
use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiTransaksiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transaksi::query()->with(['pegawai', 'promo', 'items']);

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->input('tanggal_mulai'));
        }

        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('created_at', '<=', $request->input('tanggal_akhir'));
        }

        if ($request->filled('metode_bayar')) {
            $query->where('metode_bayar', $request->input('metode_bayar'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari) {
                $q->where('kode_nota', 'like', "%{$cari}%")
                  ->orWhere('nama_pembeli', 'like', "%{$cari}%")
                  ->orWhere('nomor_hp_pembeli', 'like', "%{$cari}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $transaksi = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $transaksi->map(fn (Transaksi $t) => $this->formatTransaksi($t)),
            'pagination' => [
                'current_page' => $transaksi->currentPage(),
                'last_page'    => $transaksi->lastPage(),
                'per_page'     => $transaksi->perPage(),
                'total'        => $transaksi->total(),
            ],
        ]);
    }

    public function show(Transaksi $transaksi): JsonResponse
    {
        $transaksi->load(['pegawai', 'promo', 'items']);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatTransaksi($transaksi),
        ]);
    }

    public function void(Transaksi $transaksi): JsonResponse
    {
        if ($transaksi->status === TransaksiStatus::Void) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi ini sudah dibatalkan sebelumnya.',
            ], 422);
        }

        $berhasil = DB::transaction(function () use ($transaksi): bool {
            // Kunci barisnya DAN periksa ulang di dalam transaksi. Pemeriksaan
            // di luar memakai model hasil route-binding yang sudah basi:
            // dua permintaan void bersamaan sama-sama lolos, lalu 'terpakai'
            // dikurangi dua kali — kuota promo bertambah dari udara.
            $terkunci = Transaksi::query()->lockForUpdate()->findOrFail($transaksi->id);

            if ($terkunci->status !== TransaksiStatus::Normal) {
                return false;
            }

            $terkunci->update(['status' => TransaksiStatus::Void]);

            if ($terkunci->promo_id) {
                Promo::query()
                    ->where('id', $terkunci->promo_id)
                    ->where('terpakai', '>', 0)
                    ->decrement('terpakai');
            }

            return true;
        });

        if (! $berhasil) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya transaksi berstatus Normal yang bisa dibatalkan (void).',
            ], 422);
        }

        $transaksi->refresh();
        $transaksi->load(['pegawai', 'promo', 'items']);

        return response()->json([
            'status'  => 'success',
            'message' => "Transaksi #{$transaksi->kode_nota} berhasil dibatalkan (void).",
            'data'    => $this->formatTransaksi($transaksi),
        ]);
    }

    private function formatTransaksi(Transaksi $t): array
    {
        return [
            'id'               => $t->id,
            'kode_nota'        => $t->kode_nota,
            'local_id'         => $t->local_id,
            'status'           => $t->status->value ?? (string) $t->status,
            'metode_bayar'     => $t->metode_bayar->value ?? (string) $t->metode_bayar,
            'subtotal'         => (int) $t->subtotal,
            'diskon'           => (int) $t->diskon,
            'total'            => (int) $t->total,
            'bayar'            => (int) $t->bayar,
            'kembalian'        => (int) $t->kembalian,
            'nama_pembeli'     => $t->nama_pembeli,
            'nomor_hp_pembeli' => $t->nomor_hp_pembeli,
            'created_at'       => $t->created_at?->format('Y-m-d H:i:s'),
            'kasir'            => $t->pegawai ? [
                'id'           => $t->pegawai->id,
                'nama'         => $t->pegawai->nama_pegawai,
            ] : null,
            'promo'            => $t->promo ? [
                'id'           => $t->promo->id,
                'kode_promo'   => $t->promo->kode_promo,
                'nama_promo'   => $t->promo->nama_promo,
            ] : null,
            'items'            => $t->items->map(fn ($item) => [
                'id'           => $item->id,
                'tipe'         => $item->tipe->value ?? (string) $item->tipe,
                'produk_id'    => $item->produk_id,
                'service_id'   => $item->service_id,
                'nama_item'    => $item->nama_item,
                'jumlah'       => (int) $item->jumlah,
                'harga'        => (int) $item->harga,
                'subtotal'     => (int) $item->subtotal,
            ]),
        ];
    }
}
