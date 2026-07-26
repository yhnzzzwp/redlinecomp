<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PublicController extends Controller
{
    /**
     * Beranda = hero + katalog langsung: pengunjung melihat produk
     * tanpa harus berpindah halaman (permintaan Owner).
     */
    public function landing(Request $request): View
    {
        $query = Produk::query()->where('show_katalog', true)->with('kategori')->latest();

        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->input('kategori'));
        }

        if ($request->filled('harga_min')) {
            $query->where('harga', '>=', $request->input('harga_min'));
        }

        if ($request->filled('harga_max')) {
            $query->where('harga', '<=', $request->input('harga_max'));
        }

        if ($request->filled('cari')) {
            $query->where('nama_produk', 'like', '%' . $request->input('cari') . '%');
        }

        return view('public.landing', [
            'produk' => $query->paginate(12)->withQueryString(),
            'kategori' => KategoriProduk::all(),
            'kategori_aktif' => $request->input('kategori'),
            'harga_min' => $request->input('harga_min'),
            'harga_max' => $request->input('harga_max'),
            'cari' => $request->input('cari'),
        ]);
    }

    public function detailProduk(Produk $produk): View
    {
        abort_if(! $produk->show_katalog, 404);

        $waRaw = (string) config('redline.wa_number');
        $waNumber = preg_replace('/[^0-9]/', '', $waRaw);
        if (str_starts_with($waNumber, '0')) {
            $waNumber = '62' . substr($waNumber, 1);
        }
        $pesanWa = urlencode("Halo Redline, saya ingin memesan produk:\n\n*{$produk->nama_produk}*\nSKU: {$produk->sku}\nHarga: Rp " . number_format($produk->harga, 0, ',', '.') . "\n\nApakah stok masih tersedia?");
        $waLink = "https://wa.me/{$waNumber}?text={$pesanWa}";

        return view('public.catalogue.show', [
            'produk' => $produk,
            'waLink' => $waLink,
        ]);
    }

    public function cekServis(Request $request): View
    {
        $service = null;
        if ($request->filled('resi')) {
            $service = Service::with(['riwayat', 'parts'])->where('nomor_resi', $request->input('resi'))->first();
            if ($service) {
                if ($service->nomor_hp_customer) {
                    $service->nomor_hp_customer = '****' . substr($service->nomor_hp_customer, -4);
                }
            } else {
                session()->flash('error', 'Nomor resi tidak ditemukan.');
            }
        }

        return view('public.cek_servis', [
            'service' => $service,
            'resi' => $request->input('resi'),
            'statusList' => \App\Enums\StatusService::cases(),
        ]);
    }

    public function about(): View
    {
        return view('public.about');
    }
}
