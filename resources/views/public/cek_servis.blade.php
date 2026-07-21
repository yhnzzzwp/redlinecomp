<x-layouts.public active="Service" title="Cek Status Servis">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-page-header">
        <h1 class="rl-page-title">Lacak Status Servis</h1>
        <p class="rl-page-desc">Pantau perkembangan perbaikan perangkat Anda secara real-time.</p>
    </div>

    <div class="rl-body rl-container-700">
        <div class="rl-card p-4 mb-4 text-center">
            <h3 class="rl-section-title">Masukkan Nomor Resi</h3>
            <form method="GET" action="{{ route('cek.servis') }}" class="d-flex justify-content-center gap-2 mx-auto rl-w-400px">
                <input type="text" name="resi" value="{{ $resi }}" placeholder="Contoh: PK-1234-5678" required
                       class="rl-input w-100 text-center rl-input-mono">
                <button type="submit" class="btn-redline rl-btn-lg">Lacak</button>
            </form>
            @if(session('error'))
                <div class="mt-3 text-danger rl-text-sm">{{ session('error') }}</div>
            @endif
        </div>

        @if($service)
            <div class="rl-card p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="rl-title-md mb-1">{{ $service->nama_barang }}</h2>
                        <div class="text-muted rl-text-sm">
                            <b class="tnum rl-text-red">{{ $service->nomor_resi }}</b> &middot; {{ $service->nama_customer }}
                        </div>
                    </div>
                    <span class="rl-pill {{ $service->status->warna() }} rl-text-sm">{{ $service->status->value }}</span>
                </div>

                @php
                    $urutan = $statusList;
                    $now = array_search($service->status, $urutan, true);
                @endphp
                
                {{-- Stepper (sama seperti di admin tapi versi publik) --}}
                <div class="rl-step-wrap mb-4">
                    @foreach ($urutan as $i => $st)
                        <div class="rl-step {{ $i < $now ? 'done' : ($i === $now ? 'now' : '') }}">
                            <div class="rl-step__dot">{{ $i < $now ? '✓' : ($i === $now ? '●' : '') }}</div>
                            {{ $st->value }}
                        </div>
                    @endforeach
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <h4 class="rl-section-title mb-3">Informasi Perbaikan</h4>
                        <table class="w-100 rl-text-sm">
                            <tr><td class="text-muted py-2 rl-w-40">Tanggal Masuk</td><td class="fw-semibold">{{ $service->tanggal_masuk?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted py-2">Estimasi Selesai</td><td class="fw-semibold">{{ $service->estimasi_selesai?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted py-2">Estimasi Biaya</td><td class="fw-bold tnum">{{ $rp($service->biaya_service) }}</td></tr>
                            <tr>
                                <td class="text-muted py-2 align-top">Keluhan</td>
                                <td class="py-2 rl-text-wrap">{{ $service->masalah }}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h4 class="rl-section-title mb-3">Riwayat Status</h4>
                        <div class="rl-timeline">
                            @foreach ($service->riwayat()->latest()->get() as $r)
                                <div class="rl-timeline__item">
                                    <div class="d-flex justify-content-between mb-1">
                                        <b class="rl-text-sm">{{ $r->status->value }}</b>
                                        <span class="text-muted tnum rl-text-xs">{{ $r->created_at->format('d M, H:i') }}</span>
                                    </div>
                                    @if ($r->catatan)<div class="text-muted rl-text-sm">{{ $r->catatan }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center text-muted rl-text-sm">
                Bawa nota/resi asli saat mengambil perangkat. Pertanyaan lebih lanjut hubungi <a href="https://wa.me/{{ config('redline.wa_number') }}" target="_blank" class="text-decoration-none">WhatsApp Kami</a>.
            </div>
        @endif
    </div>
</x-layouts.public>
