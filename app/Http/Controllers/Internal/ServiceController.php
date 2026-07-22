<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\StatusService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartServiceRequest;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateStatusServiceRequest;
use App\Models\Service;
use App\Services\ServiceTicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ServiceController extends Controller
{
    public function __construct(private readonly ServiceTicketService $service) {}

    public function index(Request $request): View
    {
        $query = Service::query()->with('pegawai')->latest();

        if ($request->filled('cari')) {
            $cari = (string) $request->string('cari');
            $query->where(function ($q) use ($cari): void {
                $q->where('nomor_resi', 'like', "%{$cari}%")
                    ->orWhere('nama_customer', 'like', "%{$cari}%")
                    ->orWhere('nama_barang', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        return view('internal.service.index', [
            'service' => $query->paginate(10)->withQueryString(),
            'aktif' => Service::query()->whereNotIn('status', [
                StatusService::Selesai->value, StatusService::SudahDiambil->value,
            ])->count(),
            'cari' => $request->string('cari')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('internal.service.form', [
            'teknisi' => \App\Models\Pegawai::query()->where('masih_bekerja', true)->get(),
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $service = $this->service->buat($request->validated(), $request->user());

        return redirect()->route('service.show', $service)
            ->with('success', "Tiket servis dibuat. Nomor resi: {$service->nomor_resi}");
    }

    public function show(Service $service): View
    {
        $service->load(['pegawai', 'riwayat.pegawai', 'parts', 'teknisi']);
        $produkList = \App\Models\Produk::query()
            ->with('kategori')
            ->orderBy('nama_produk')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nama' => $p->nama_produk,
                'harga' => (int) $p->harga,
                'stok' => (int) $p->jumlah_produk,
                'kategori' => $p->kategori?->nama_kategori ?? 'Produk',
            ]);

        return view('internal.service.show', [
            'service' => $service,
            'produkList' => $produkList,
        ]);
    }

    public function updateStatus(UpdateStatusServiceRequest $request, Service $service): RedirectResponse
    {
        [$service, $waLink] = $this->service->updateStatus(
            $service,
            StatusService::from($request->validated('status')),
            $request->validated('catatan'),
            $request->user(),
        );

        $response = redirect()->route('service.show', $service)->with('success', 'Status servis diperbarui.');
        
        if ($waLink) {
            $response->with('wa_link', $waLink);
        }

        return $response;
    }

    public function storePart(StorePartServiceRequest $request, Service $service): RedirectResponse
    {
        $this->service->tambahPart($service, $request->validated());

        return redirect()->route('service.show', $service)->with('success', 'Sparepart ditambahkan.');
    }
}
