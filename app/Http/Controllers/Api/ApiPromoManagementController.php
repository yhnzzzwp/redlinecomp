<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TipePromo;
use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiPromoManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Promo::query();

        if ($request->has('aktif') && $request->input('aktif') !== '') {
            $query->where('aktif', filter_var($request->input('aktif'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari) {
                $q->where('nama_promo', 'like', "%{$cari}%")
                  ->orWhere('kode_promo', 'like', "%{$cari}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $promos = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => $promos->items(),
            'pagination' => [
                'current_page' => $promos->currentPage(),
                'last_page'    => $promos->lastPage(),
                'per_page'     => $promos->perPage(),
                'total'        => $promos->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_promo'        => ['required', 'string', 'max:255'],
            'kode_promo'        => ['required', 'string', 'max:50', 'unique:promo,kode_promo'],
            'tipe_promo'        => ['required', Rule::enum(TipePromo::class)],
            // Batas 100 untuk tipe Persen ada di StorePromoRequest (form web)
            // tetapi hilang di API — lewat sini promo 'persen' bisa dibuat
            // dengan besaran 1000, dan diskonnya dipangkas jadi seluruh
            // subtotal: barang terbawa pulang gratis.
            'besar_promo'       => ['required', 'integer', 'min:1',
                $request->input('tipe_promo') === \App\Enums\TipePromo::Persen->value
                    ? 'max:100'
                    : 'max:100000000000'],
            'minimal_transaksi' => ['nullable', 'integer', 'min:0'],
            'maksimal_diskon'   => ['nullable', 'integer', 'min:0'],
            // Kolomnya NOT NULL di basis data. Dengan 'nullable' di sini,
            // permintaan tanpa tanggal lolos validasi lalu gagal di lapisan SQL
            // — pemanggil menerima 500 alih-alih 422 yang menjelaskan apa yang
            // kurang.
            'waktu_mulai'       => ['required', 'date'],
            'waktu_berakhir'    => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'kuota'             => ['nullable', 'integer', 'min:1'],
            'aktif'             => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data promo tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $promo = Promo::create([
            'nama_promo'        => $request->input('nama_promo'),
            'kode_promo'        => strtoupper((string) $request->input('kode_promo')),
            'tipe_promo'        => $request->input('tipe_promo'),
            'besar_promo'       => (int) $request->input('besar_promo'),
            'minimal_transaksi' => (int) $request->input('minimal_transaksi', 0),
            'maksimal_diskon'   => $request->input('maksimal_diskon') ? (int) $request->input('maksimal_diskon') : null,
            'waktu_mulai'       => $request->input('waktu_mulai'),
            'waktu_berakhir'    => $request->input('waktu_berakhir'),
            'kuota'             => $request->input('kuota') ? (int) $request->input('kuota') : null,
            'aktif'             => $request->boolean('aktif', true),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Promo berhasil dibuat.',
            'data'    => $promo,
        ], 201);
    }

    public function show(Promo $promo): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => $promo,
        ]);
    }

    public function update(Request $request, Promo $promo): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_promo'        => ['required', 'string', 'max:255'],
            'kode_promo'        => ['required', 'string', 'max:50', Rule::unique('promo', 'kode_promo')->ignore($promo->id)],
            'tipe_promo'        => ['required', Rule::enum(TipePromo::class)],
            // Batas 100 untuk tipe Persen ada di StorePromoRequest (form web)
            // tetapi hilang di API — lewat sini promo 'persen' bisa dibuat
            // dengan besaran 1000, dan diskonnya dipangkas jadi seluruh
            // subtotal: barang terbawa pulang gratis.
            'besar_promo'       => ['required', 'integer', 'min:1',
                $request->input('tipe_promo') === \App\Enums\TipePromo::Persen->value
                    ? 'max:100'
                    : 'max:100000000000'],
            'minimal_transaksi' => ['nullable', 'integer', 'min:0'],
            'maksimal_diskon'   => ['nullable', 'integer', 'min:0'],
            // Kolomnya NOT NULL di basis data. Dengan 'nullable' di sini,
            // permintaan tanpa tanggal lolos validasi lalu gagal di lapisan SQL
            // — pemanggil menerima 500 alih-alih 422 yang menjelaskan apa yang
            // kurang.
            'waktu_mulai'       => ['required', 'date'],
            'waktu_berakhir'    => ['required', 'date', 'after_or_equal:waktu_mulai'],
            'kuota'             => ['nullable', 'integer', 'min:1'],
            'aktif'             => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data promo tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $promo->update([
            'nama_promo'        => $request->input('nama_promo'),
            'kode_promo'        => strtoupper((string) $request->input('kode_promo')),
            'tipe_promo'        => $request->input('tipe_promo'),
            'besar_promo'       => (int) $request->input('besar_promo'),
            'minimal_transaksi' => (int) $request->input('minimal_transaksi', 0),
            'maksimal_diskon'   => $request->input('maksimal_diskon') ? (int) $request->input('maksimal_diskon') : null,
            'waktu_mulai'       => $request->input('waktu_mulai'),
            'waktu_berakhir'    => $request->input('waktu_berakhir'),
            'kuota'             => $request->input('kuota') ? (int) $request->input('kuota') : null,
            'aktif'             => $request->has('aktif') ? $request->boolean('aktif') : $promo->aktif,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Promo berhasil diperbarui.',
            'data'    => $promo,
        ]);
    }

    public function destroy(Promo $promo): JsonResponse
    {
        $promo->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Promo berhasil dihapus.',
        ]);
    }

    public function toggle(Promo $promo): JsonResponse
    {
        $promo->update(['aktif' => ! $promo->aktif]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status promo berhasil diubah.',
            'data'    => $promo,
        ]);
    }
}
