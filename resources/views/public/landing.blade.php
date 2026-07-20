<x-layouts.public active="Home" title="Hardware & Servis Premium">
    <section class="rl-hero text-center">
        <h1 class="fw-bold" style="font-size:38px;letter-spacing:-.6px">Upgrade Performa<br><span style="color:var(--red)">Komputer Anda</span></h1>
        <p class="mx-auto mt-3" style="max-width:52ch;color:#c7d2df;font-size:14px">Rasakan kecepatan ekstrim dengan teknologi hardware terbaru. Presisi tinggi untuk gaming profesional dan produktivitas tanpa hambatan.</p>
        <div class="d-flex gap-2 justify-content-center mt-4">
            <a href="{{ route('catalogue') }}" class="btn-redline">Belanja Sekarang</a>
            <a href="{{ route('catalogue') }}" class="btn-ghost">Lihat Katalog</a>
        </div>
    </section>

    <section class="p-4" style="max-width:1100px;margin:0 auto">
        <h2 class="fw-bold mb-3" style="font-size:22px">Katalog Produk</h2>
        <div class="row g-3">
            @foreach (\App\Models\Produk::where('show_katalog', true)->limit(8)->get() as $p)
                <div class="col-6 col-md-3">
                    <div class="rl-card h-100 overflow-hidden">
                        <div style="height:120px;background:linear-gradient(135deg,#1b1e23,#3a3e45)"></div>
                        <div class="p-3">
                            <div class="fw-semibold" style="font-size:13.5px">{{ $p->nama_produk }}</div>
                            <div class="fw-bold mt-1" style="color:var(--red-strong)">Rp {{ number_format($p->harga, 0, ',', '.') }}</div>
                            <span class="rl-pill {{ $p->jumlah_produk == 0 ? 'red' : ($p->jumlah_produk <= 5 ? 'amber' : 'green') }} mt-2" style="font-size:9px">{{ $p->statusStok() }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
