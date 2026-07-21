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
        <h2 class="rl-title-lg mb-3">Katalog Produk</h2>
        <div class="d-flex gap-3 overflow-auto pb-4 rl-carousel">
            @foreach (\App\Models\Produk::where('show_katalog', true)->limit(10)->get() as $p)
                <div class="rl-carousel-item">
                    <a href="{{ route('catalogue.show', $p) }}" class="rl-card h-100 overflow-hidden d-block text-decoration-none text-dark">
                        <div class="rl-catalogue-img-sm">
                            @if ($p->foto_produk)
                                <img src="{{ Storage::url($p->foto_produk) }}" alt="{{ $p->nama_produk }}" class="rl-catalogue-img">
                            @else
                                <div class="rl-catalogue-placeholder">📷</div>
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
</x-layouts.public>
