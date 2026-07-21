<x-layouts.public active="Home" title="Hardware & Servis Premium">
    <section class="rl-hero text-center">
        <h1 class="rl-hero-title">Upgrade Performa<br><span class="rl-text-red">Komputer Anda</span></h1>
        <p class="rl-hero-desc">Rasakan kecepatan ekstrim dengan teknologi hardware terbaru. Presisi tinggi untuk gaming profesional dan produktivitas tanpa hambatan.</p>
        <div class="d-flex gap-2 justify-content-center mt-4">
            <a href="{{ route('catalogue') }}" class="btn-redline">Belanja Sekarang</a>
            <a href="{{ route('catalogue') }}" class="btn-ghost">Lihat Katalog</a>
        </div>
    </section>

    <section class="p-4 rl-container">
        <div class="row g-3">
            @php
                $fitur = [
                    ['Garansi Resmi', 'Setiap produk bergaransi dan diuji sebelum dikirim.', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>'],
                    ['Servis Cepat', 'Diagnosa hari yang sama dengan estimasi biaya di muka.', '<path d="M14.7 6.3a4 4 0 0 0 5 5l-8.5 8.5a2.1 2.1 0 0 1-3-3L16.7 8.3"/><path d="m9 11 3 3"/>'],
                    ['Custom PC Build', 'Rakitan disesuaikan kebutuhan gaming & produktivitas.', '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/>'],
                ];
            @endphp
            @foreach ($fitur as $i => $f)
                <div class="col-md-4" data-reveal style="--reveal-d: {{ $i * 90 }}ms">
                    <div class="rl-card h-100 p-4 d-flex align-items-start gap-3">
                        <span class="rl-feature-ico">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">{!! $f[2] !!}</svg>
                        </span>
                        <div>
                            <div class="fw-bold mb-1" style="font-size:15px">{{ $f[0] }}</div>
                            <div class="rl-text-muted rl-text-sm">{{ $f[1] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="p-4 rl-container">
        <div class="d-flex align-items-center justify-content-between mb-3" data-reveal>
            <h2 class="rl-title-lg mb-0">Katalog Produk</h2>
            <a href="{{ route('catalogue') }}" class="rl-text-red text-decoration-none fw-semibold rl-text-sm">Lihat semua &rarr;</a>
        </div>
        <div class="d-flex gap-3 overflow-auto pb-4 rl-carousel" data-reveal>
            @foreach (\App\Models\Produk::where('show_katalog', true)->limit(10)->get() as $p)
                <div class="rl-carousel-item">
                    <a href="{{ route('catalogue.show', $p) }}" class="rl-card h-100 overflow-hidden d-block text-decoration-none text-dark">
                        <div class="rl-catalogue-img-sm">
                            @if ($p->foto_produk)
                                <img src="{{ Storage::url($p->foto_produk) }}" alt="{{ $p->nama_produk }}" class="rl-catalogue-img">
                            @else
                                <x-ui.hardware-thumb :kategori="$p->kategori?->nama_kategori" />
                            @endif
                        </div>
                        <div class="p-3 d-flex flex-column">
                            <div class="fw-semibold text-truncate mb-1 rl-text-sm">{{ $p->nama_produk }}</div>
                            <div class="fw-bold mb-3 rl-text-total">Rp {{ number_format($p->harga, 0, ',', '.') }}</div>
                            <div class="mt-auto">
                                @if ($p->jumlah_produk > 5)
                                    <span class="rl-pill green">Tersedia</span>
                                @elseif ($p->jumlah_produk > 0)
                                    <span class="rl-pill amber">Stok Terbatas</span>
                                @else
                                    <span class="rl-pill red">Habis Terjual</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    <section class="p-4 rl-container">
        <div class="rl-service-cta" data-reveal>
            <div>
                <h2 class="fw-bold mb-1" style="font-size:22px">Perangkat Anda bermasalah?</h2>
                <p class="mb-0" style="color:#c7d2df;font-size:13.5px">Lacak status perbaikan secara real-time hanya dengan nomor resi servis.</p>
            </div>
            <a href="{{ route('cek.servis') }}" class="btn-redline flex-shrink-0">Lacak Servis</a>
        </div>
    </section>
</x-layouts.public>
