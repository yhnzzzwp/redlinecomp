<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use App\Models\Produk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiProdukController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Produk::query()->with('kategori');

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->input('kategori_id'));
        }

        if ($request->has('show_katalog') && $request->input('show_katalog') !== '') {
            $query->where('show_katalog', filter_var($request->input('show_katalog'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('cari')) {
            $cari = trim((string) $request->input('cari'));
            $query->where(function ($q) use ($cari) {
                $q->where('nama_produk', 'like', "%{$cari}%")
                  ->orWhere('sku', 'like', "%{$cari}%")
                  ->orWhere('deskripsi_produk', 'like', "%{$cari}%");
            });
        }

        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));
        $produk = $query->latest('id')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $produk->items(),
            'pagination' => [
                'current_page' => $produk->currentPage(),
                'last_page'    => $produk->lastPage(),
                'per_page'     => $produk->perPage(),
                'total'        => $produk->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_produk'      => ['required', 'string', 'max:255'],
            'sku'              => ['nullable', 'string', 'max:50', 'unique:produk,sku'],
            'kategori_id'      => ['required', 'exists:kategori_produk,id'],
            'deskripsi_produk' => ['nullable', 'string'],
            'show_katalog'     => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data produk tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $sku = $request->input('sku');
        if (empty($sku)) {
            $sku = 'PRD-' . strtoupper(\Illuminate\Support\Str::random(6));
        }

        $produk = Produk::create([
            'nama_produk'      => $request->input('nama_produk'),
            'sku'              => $sku,
            'kategori_id'      => $request->input('kategori_id'),
            'deskripsi_produk' => $request->input('deskripsi_produk'),
            'show_katalog'     => $request->boolean('show_katalog', true),
        ]);

        $produk->load('kategori');

        return response()->json([
            'status'  => 'success',
            'message' => 'Produk berhasil ditambahkan.',
            'data'    => $produk,
        ], 201);
    }

    public function show(Produk $produk): JsonResponse
    {
        $produk->load('kategori');

        return response()->json([
            'status' => 'success',
            'data'   => $produk,
        ]);
    }

    public function update(Request $request, Produk $produk): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_produk'      => ['required', 'string', 'max:255'],
            'sku'              => ['nullable', 'string', 'max:50', Rule::unique('produk', 'sku')->ignore($produk->id)],
            'kategori_id'      => ['required', 'exists:kategori_produk,id'],
            'deskripsi_produk' => ['nullable', 'string'],
            'show_katalog'     => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data produk tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $produk->update([
            'nama_produk'      => $request->input('nama_produk'),
            'sku'              => $request->input('sku', $produk->sku),
            'kategori_id'      => $request->input('kategori_id'),
            'deskripsi_produk' => $request->input('deskripsi_produk'),
            'show_katalog'     => $request->has('show_katalog') ? $request->boolean('show_katalog') : $produk->show_katalog,
        ]);

        $produk->load('kategori');

        return response()->json([
            'status'  => 'success',
            'message' => 'Produk berhasil diperbarui.',
            'data'    => $produk,
        ]);
    }

    public function destroy(Produk $produk): JsonResponse
    {
        $produk->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    public function kategoriIndex(): JsonResponse
    {
        $kategori = KategoriProduk::withCount('produk')->orderBy('nama_kategori')->get();

        return response()->json([
            'status' => 'success',
            'data'   => $kategori,
        ]);
    }

    public function kategoriStore(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_kategori' => ['required', 'string', 'max:255', 'unique:kategori_produk,nama_kategori'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kategori tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kategori = KategoriProduk::create([
            'nama_kategori' => $request->input('nama_kategori'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori berhasil ditambahkan.',
            'data'    => $kategori,
        ], 201);
    }

    public function kategoriUpdate(Request $request, KategoriProduk $kategori): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nama_kategori' => ['required', 'string', 'max:255', Rule::unique('kategori_produk', 'nama_kategori')->ignore($kategori->id)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kategori tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kategori->update([
            'nama_kategori' => $request->input('nama_kategori'),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori berhasil diperbarui.',
            'data'    => $kategori,
        ]);
    }

    public function kategoriDestroy(KategoriProduk $kategori): JsonResponse
    {
        if ($kategori->produk()->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kategori tidak dapat dihapus karena masih memiliki produk terkait.',
            ], 422);
        }

        $kategori->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
