<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProdukController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    public function index(Request $request): View
    {
        $query = Produk::query()->with('kategori')->latest();

        if ($request->filled('cari')) {
            $cari = (string) $request->string('cari');
            $query->where(function ($q) use ($cari): void {
                $q->where('nama_produk', 'like', "%{$cari}%")
                    ->orWhere('sku', 'like', "%{$cari}%");
            });
        }

        return view('internal.produk.index', [
            'produk' => $query->paginate(10)->withQueryString(),
            'total' => Produk::query()->count(),
            'cari' => $request->string('cari')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('internal.produk.form', [
            'produk' => new Produk(),
            'kategori' => KategoriProduk::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(StoreProdukRequest $request): RedirectResponse
    {
        $this->service->create($request->safe()->except('foto'), $request->file('foto'));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk): View
    {
        return view('internal.produk.form', [
            'produk' => $produk,
            'kategori' => KategoriProduk::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(UpdateProdukRequest $request, Produk $produk): RedirectResponse
    {
        $this->service->update($produk, $request->safe()->except('foto'), $request->file('foto'));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $this->service->delete($produk);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }
}
