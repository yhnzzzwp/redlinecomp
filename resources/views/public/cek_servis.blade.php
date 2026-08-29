<x-layouts.public active="Service" title="Cek Status Servis">
    @php
        $rp = \App\Support\Uang::rupiah(...);
    @endphp

    <div class="rl-public-header">
        <div class="rl-kicker mb-2">Pit stop <b>servis</b></div>
        <h1 class="rl-page-title">Lacak Status Servis</h1>
        <p class="rl-page-desc">Pantau perkembangan perbaikan perangkat Anda secara real-time.</p>
        <div class="rl-ticks rl-ticks--dark"></div>
    </div>

    <div class="rl-body rl-container-700 my-5 pb-5">
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
                        <h2 class="rl-title-md mb-1">{{ $service->perangkat->merk_model }}</h2>
                        <div class="text-muted rl-text-sm">
                            <b class="tnum rl-text-red">{{ $service->nomor_resi }}</b> &middot; {{ \App\Support\Privasi::namaSingkat($service->perangkat->nama_customer) }}
                        </div>
                    </div>
                    <span class="rl-pill {{ $service->status->warna() }} rl-text-sm">{{ $service->status->value }}</span>
                </div>

                @php
                    $urutan = $statusList;
                    $now = array_search($service->status, $urutan, true);
                @endphp

                <div class="rl-step-wrap mb-4" role="list" aria-label="Tahapan servis">
                    @foreach ($urutan as $i => $st)
                        <div class="rl-step {{ $i < $now ? 'done' : ($i === $now ? 'now' : '') }}" role="listitem" @if($i === $now) aria-current="step" @endif>
                            <div class="rl-step__dot">{!! $i < $now ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-label="Sukses"><polyline points="20 6 9 17 4 12"/></svg>' : ($i === $now ? '●' : '') !!}</div>
                            {{ $st->value }}
                        </div>
                    @endforeach
                </div>

                @if($service->status === \App\Enums\StatusService::Selesai)
                    <div class="rl-alert rl-alert--success p-3 mb-4 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h4 class="fw-bold m-0 text-success rl-text-sm">✔ Servis Selesai &amp; Siap Diambil</h4>
                            <div class="rl-text-xs text-muted">Silakan mengambil perangkat Anda di toko Redline Komputer.</div>
                        </div>
                        <div class="text-end ms-auto">
                            <span class="rl-text-xs text-muted d-block">Total Biaya Pembayaran</span>
                            <span class="fs-5 fw-bold text-success tnum">{{ $rp($service->totalBiaya()) }}</span>
                        </div>
                    </div>
                @endif

                <div class="row g-4">
                    <div class="col-md-6">
                        <h4 class="rl-section-title mb-3">Informasi Perbaikan</h4>
                        <table class="w-100 rl-text-sm">
                            <tr><td class="text-muted py-2 rl-w-40">Tanggal Masuk</td><td class="fw-semibold">{{ $service->tanggal_masuk?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted py-2">Estimasi Selesai</td><td class="fw-semibold">{{ $service->estimasi_selesai?->format('d M Y') ?? '—' }}</td></tr>
                            <tr><td class="text-muted py-2">Biaya Jasa Servis</td><td class="fw-semibold tnum">{{ $rp($service->biaya_service) }}</td></tr>
                            @if($service->parts->count() > 0)
                                <tr><td class="text-muted py-2">Biaya Suku Cadang</td><td class="fw-semibold tnum">{{ $rp($service->parts->sum('subtotal')) }}</td></tr>
                            @endif
                            <tr class="border-top"><td class="fw-bold py-2">Total Biaya</td><td class="fw-bold text-danger tnum fs-6">{{ $rp($service->totalBiaya()) }}</td></tr>
                            <tr>
                                <td class="text-muted py-2 align-top">Keluhan</td>
                                <td class="py-2 rl-text-wrap">{{ $service->keluhan }}</td>
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

            @php
                $waClean = preg_replace('/[^0-9]/', '', (string) config('redline.wa_number'));
                if (str_starts_with($waClean, '0')) { $waClean = '62' . substr($waClean, 1); }
            @endphp
            <div class="text-center text-muted rl-text-sm">
                Bawa nota/resi asli saat mengambil perangkat. Pertanyaan lebih lanjut hubungi <a href="https://wa.me/{{ $waClean }}" target="_blank" class="text-decoration-none">WhatsApp Kami</a>.
            </div>
        @endif
    </div>
</x-layouts.public>
