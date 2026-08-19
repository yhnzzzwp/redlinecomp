<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Produk::query()
            ->with('kategori')
            ->where('show_katalog', true);

        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->input('kategori'));
        }

        if ($request->filled('cari')) {
            $cari = $request->input('cari');
            $query->where(function ($q) use ($cari) {
                $q->where('nama_produk', 'like', "%{$cari}%")
                  ->orWhere('sku', 'like', "%{$cari}%")
                  ->orWhere('deskripsi_produk', 'like', "%{$cari}%");
            });
        }

        $produk = $query->latest('id')->paginate(12)->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => $produk->items(),
            'pagination' => [
                'current_page' => $produk->currentPage(),
                'last_page' => $produk->lastPage(),
                'per_page' => $produk->perPage(),
                'total' => $produk->total(),
            ],
        ]);
    }

    public function show(Produk $produk): JsonResponse
    {
        if (! $produk->show_katalog) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan dalam katalog',
            ], 404);
        }

        $produk->load('kategori');

        $terkait = Produk::query()
            ->where('show_katalog', true)
            ->where('id', '!=', $produk->id)
            ->when($produk->kategori_id, fn ($q) => $q->where('kategori_id', $produk->kategori_id))
            ->limit(4)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'produk' => $produk,
                'terkait' => $terkait,
            ],
        ]);
    }

    public function kategori(): JsonResponse
    {
        $kategori = KategoriProduk::withCount(['produk' => fn ($q) => $q->where('show_katalog', true)])->get();

        return response()->json([
            'status' => 'success',
            'data' => $kategori,
        ]);
    }
}
