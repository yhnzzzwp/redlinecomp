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

    public function landing(Request $request): View
    {
        $query = Produk::query()->where('show_katalog', true)->with('kategori')->latest();

        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->input('kategori'));
        }

        if ($request->filled('cari')) {
            $query->where('nama_produk', 'like', '%' . $request->input('cari') . '%');
        }

        return view('public.landing', [
            'produk' => $query->paginate(12)->withQueryString(),
            'kategori' => KategoriProduk::all(),
            'kategori_aktif' => $request->input('kategori'),
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
        $pesanWa = urlencode("Halo Redline, saya ingin bertanya tentang produk:\n\n*{$produk->nama_produk}*\nSKU: {$produk->sku}");
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
            $service = Service::with(['riwayat', 'parts', 'perangkat'])
                ->resiSetara(trim((string) $request->input('resi')))
                ->first();
            if ($service) {
                if ($service->perangkat && $service->perangkat->nomor_hp_customer) {
                    $service->perangkat->nomor_hp_customer = '****' . substr($service->perangkat->nomor_hp_customer, -4);
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
