<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Exceptions\PosException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiRequest;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Transaksi;
use App\Services\PosService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View as ViewInstance;

final class PosController extends Controller
{
    public function __construct(private readonly PosService $posService) {}

    public function index(): ViewInstance
    {
        return view('internal.pos', [
            'produk' => Produk::query()->with('kategori')->orderBy('nama_produk')->get(),
            'services' => \App\Models\Service::query()
                ->whereNotIn('status', [\App\Enums\StatusService::Selesai, \App\Enums\StatusService::SudahDiambil])
                ->get(),
            'kategori' => KategoriProduk::query()->orderBy('nama_kategori')->get(),
            'promo' => Promo::query()->where('aktif', true)->get(),
        ]);
    }

    public function store(StoreTransaksiRequest $request): RedirectResponse
    {
        try {
            $transaksi = $this->posService->checkout($request->toCheckoutData(), $request->user());
        } catch (PosException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('pos.nota', $transaksi)
            ->with('success', "Transaksi berhasil. Nota #{$transaksi->kode_nota}");
    }

    public function nota(Transaksi $transaksi): Response
    {
        $transaksi->load(['items', 'promo', 'pegawai']);

        return Pdf::loadView('pdf.nota', ['t' => $transaksi])
            ->stream("nota-{$transaksi->kode_nota}.pdf");
    }

    /** Struk 80mm untuk printer thermal — dicetak lewat dialog print browser. */
    public function struk(Transaksi $transaksi): ViewInstance|View
    {
        $transaksi->load(['items', 'promo', 'pegawai']);

        return view('internal.struk', ['t' => $transaksi]);
    }
}
