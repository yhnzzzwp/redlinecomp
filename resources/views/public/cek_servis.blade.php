<x-layouts.public active="Service" title="Cek Status Servis">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-page-header">
        <h1 class="rl-page-title">Lacak Status Servis</h1>
        <p class="rl-page-desc">Pantau perkembangan perbaikan perangkat Anda secara real-time.</p>
    </div>

    <div class="rl-body mx-auto" style="max-width:700px">
        <div class="rl-card p-4 mb-4 text-center">
            <h3 class="rl-section-title">Masukkan Nomor Resi</h3>
            <form method="GET" action="{{ route('cek.servis') }}" class="d-flex justify-content-center gap-2 max-w-md mx-auto" style="max-width:400px">
                <input type="text" name="resi" value="{{ $resi }}" placeholder="Contoh: PK-1234-5678" required
                       class="rl-input w-100 text-center" style="letter-spacing:1px;font-family:monospace">
                <button type="submit" class="btn-redline" style="padding:12px 24px">Lacak</button>
            </form>
            @if(session('error'))
                <div class="mt-3 text-danger" style="font-size:13px">{{ session('error') }}</div>
            @endif
        </div>

        @if($service)
            <div class="rl-card p-4 mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fw-bold mb-1" style="font-size:20px">{{ $service->nama_barang }}</h2>
                        <div class="text-muted" style="font-size:13px">
                            <b class="tnum" style="color:var(--red-strong)">{{ $service->nomor_resi }}</b> &middot; {{ $service->nama_customer }}
                        </div>
                    </div>
                    <span class="rl-pill {{ $service->status->warna() }}" style="font-size:13px">{{ $service->status->value }}</span>
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
                        <h4 class="fw-bold mb-3" style="font-size:15px">Informasi Perbaikan</h4>
                        <table style="width:100%;font-size:13px">
                            <tr><td class="text-muted py-2" style="width:40%">Tanggal Masuk</td><td class="fw-semibold">{{ $service->tanggal_masuk?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted py-2">Estimasi Selesai</td><td class="fw-semibold">{{ $service->estimasi_selesai?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted py-2">Estimasi Biaya</td><td class="fw-bold tnum">{{ $rp($service->biaya_service) }}</td></tr>
                            <tr>
                                <td class="text-muted py-2 align-top">Keluhan</td>
                                <td class="py-2" style="white-space:pre-wrap">{{ $service->masalah }}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h4 class="fw-bold mb-3" style="font-size:15px">Riwayat Status</h4>
                        <div class="rl-timeline">
                            @foreach ($service->riwayat()->latest()->get() as $r)
                                <div class="rl-timeline__item">
                                    <div class="d-flex justify-content-between mb-1">
                                        <b style="font-size:13px">{{ $r->status->value }}</b>
                                        <span class="text-muted tnum" style="font-size:11px">{{ $r->created_at->format('d M, H:i') }}</span>
                                    </div>
                                    @if ($r->catatan)<div class="text-muted" style="font-size:12px">{{ $r->catatan }}</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center text-muted" style="font-size:12px">
                Bawa nota/resi asli saat mengambil perangkat. Pertanyaan lebih lanjut hubungi <a href="https://wa.me/{{ config('redline.wa_number') }}" target="_blank" class="text-decoration-none">WhatsApp Kami</a>.
            </div>
        @endif
    </div>
</x-layouts.public>
