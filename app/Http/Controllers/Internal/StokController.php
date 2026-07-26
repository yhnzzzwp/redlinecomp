<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\TipeMutasiStok;
use App\Http\Controllers\Controller;
use App\Models\MutasiStok;
use App\Models\Produk;
use App\Services\StokService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Stok opname (pencocokan stok fisik) + riwayat mutasi stok. */
final class StokController extends Controller
{
    public function __construct(private readonly StokService $stok) {}

    public function opname(): View
    {
        return view('internal.stok.opname', [
            'produk' => Produk::query()->with('kategori')->orderBy('nama_produk')->get(),
        ]);
    }

    public function simpanOpname(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stok' => ['required', 'array'],
            'stok.*' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $catatan = trim((string) ($data['catatan'] ?? ''));
        $keterangan = 'Opname fisik' . ($catatan !== '' ? ': ' . $catatan : '');

        $disesuaikan = 0;
        DB::transaction(function () use ($data, $keterangan, &$disesuaikan): void {
            foreach ($data['stok'] as $produkId => $fisik) {
                if ($fisik === null || $fisik === '') {
                    continue;
                }

                $produk = Produk::query()->lockForUpdate()->find($produkId);
                if (! $produk) {
                    continue;
                }

                $sistem = (int) $produk->jumlah_produk;
                $fisik = (int) $fisik;
                if ($fisik === $sistem) {
                    continue;
                }

                $produk->update(['jumlah_produk' => $fisik]);
                $this->stok->catat($produk, $sistem, $fisik, TipeMutasiStok::Opname, $keterangan);
                $disesuaikan++;
            }
        });

        return redirect()->route('stok.opname')->with('success', $disesuaikan > 0
            ? "Opname tersimpan: {$disesuaikan} produk disesuaikan dengan stok fisik."
            : 'Opname selesai: tidak ada selisih — semua stok sudah cocok.');
    }

    public function mutasi(Request $request): View
    {
        $query = MutasiStok::query()->with(['produk', 'pegawai'])->latest('id');

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->string('tipe')->toString());
        }

        if ($request->filled('produk_id')) {
            $query->where('produk_id', $request->integer('produk_id'));
        }

        if ($request->filled('cari')) {
            $cari = $request->string('cari')->toString();
            $query->whereHas('produk', function ($q) use ($cari): void {
                $q->where('nama_produk', 'like', "%{$cari}%")->orWhere('sku', 'like', "%{$cari}%");
            });
        }

        return view('internal.stok.mutasi', [
            'mutasi' => $query->paginate(20)->withQueryString(),
            'tipeList' => TipeMutasiStok::cases(),
            'tipeAktif' => $request->input('tipe'),
            'cari' => $request->string('cari')->toString(),
            'produkFilter' => $request->filled('produk_id') ? Produk::query()->find($request->integer('produk_id')) : null,
        ]);
    }
}
