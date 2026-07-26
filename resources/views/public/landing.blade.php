<x-layouts.public active="Home" title="Hardware & Servis Komputer">
    <section class="rl-hero text-center">
        <div class="rl-kicker mb-3">Redline Komputer <b>·</b> Salatiga</div>
        <h1 class="rl-hero-title">Tembus Batas<br><i>Performa.</i></h1>
        <p class="rl-hero-desc">Hardware pilihan yang diuji satu per satu, rakitan presisi, dan servis dengan estimasi biaya di muka. Dari workstation harian sampai mesin gaming yang digeber sampai garis merah.</p>
        <div class="d-flex gap-2 justify-content-center mt-4">
            <a href="{{ route('catalogue') }}" class="btn-redline rl-btn-lg">Jelajahi Katalog</a>
            <a href="{{ route('cek.servis') }}" class="btn-ghost rl-btn-lg">Lacak Servis</a>
        </div>

        <div class="rl-hero-stats">
            <div class="rl-hero-stat">
                <div class="rl-hero-stat__val">SEJAK <i>2016</i></div>
                <div class="rl-hero-stat__label">Melayani Salatiga</div>
            </div>
            <div class="rl-hero-stat">
                <div class="rl-hero-stat__val">&plusmn;<i>24 JAM</i></div>
                <div class="rl-hero-stat__label">Diagnosa Servis</div>
            </div>
            <div class="rl-hero-stat">
                <div class="rl-hero-stat__val">GARANSI <i>RESMI</i></div>
                <div class="rl-hero-stat__label">Semua Produk</div>
            </div>
        </div>
    </section>

    <div class="rl-ticks rl-ticks--dark"></div>

    <section class="p-4 rl-container">
        <div class="row g-3 mt-1">
            @php
                $fitur = [
                    ['Garansi Resmi', 'Setiap produk bergaransi dan diuji sebelum diserahkan.', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>'],
                    ['Servis Transparan', 'Diagnosa cepat, estimasi biaya disepakati di muka.', '<path d="M14.7 6.3a4 4 0 0 0 5 5l-8.5 8.5a2.1 2.1 0 0 1-3-3L16.7 8.3"/><path d="m9 11 3 3"/>'],
                    ['Custom PC Build', 'Rakitan dikalibrasi untuk gaming maupun produktivitas.', '<rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v2M15 2v2M9 20v2M15 20v2M2 9h2M2 15h2M20 9h2M20 15h2"/>'],
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
        <div class="d-flex align-items-end justify-content-between mb-3" data-reveal>
            <div>
                <div class="rl-kicker mb-1">Baru masuk <b>gudang</b></div>
                <h2 class="rl-title-lg mb-0">Katalog Produk</h2>
            </div>
            <a href="{{ route('catalogue') }}" class="rl-text-red text-decoration-none fw-semibold rl-text-sm">Lihat semua &rarr;</a>
        </div>
        <div class="d-flex gap-3 overflow-auto pb-4 rl-carousel" data-reveal>
            @foreach ($unggulan as $p)
                <div class="rl-carousel-item">
                    <a href="{{ route('catalogue.show', $p) }}" class="rl-card h-100 overflow-hidden d-block text-decoration-none text-dark">
                        <div class="p-3 d-flex flex-column">
                            <div class="rl-text-caption rl-mono mb-1">{{ $p->sku }}</div>
                            <div class="fw-semibold text-truncate mb-1">{{ $p->nama_produk }}</div>
                            <p class="rl-text-sm rl-text-muted text-truncate mb-2">{{ $p->deskripsi_produk ?: 'Belum ada deskripsi untuk produk ini.' }}</p>
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
                <div class="rl-kicker mb-2">Pit stop <b>servis</b></div>
                <h2 class="rl-title-md text-white mb-1">Perangkat Anda bermasalah?</h2>
                <p class="mb-0" style="color:var(--pub-muted);font-size:13.5px">Lacak status perbaikan secara real-time cukup dengan nomor resi &mdash; contoh: <span class="rl-mono" style="color:#fff">PK-1234-5678</span></p>
            </div>
            <a href="{{ route('cek.servis') }}" class="btn-redline flex-shrink-0">Lacak Servis</a>
        </div>
    </section>
</x-layouts.public>
