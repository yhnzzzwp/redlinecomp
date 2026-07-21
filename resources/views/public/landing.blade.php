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
        <div class="d-flex gap-3 overflow-auto pb-4 rl-carousel" style="scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: none; margin-left: -16px; margin-right: -16px; padding-left: 16px; padding-right: 16px;">
            @foreach (\App\Models\Produk::where('show_katalog', true)->limit(10)->get() as $p)
                <div style="flex: 0 0 220px; scroll-snap-align: start;">
                    <a href="{{ route('catalogue.show', $p) }}" class="rl-card h-100 overflow-hidden d-block text-decoration-none" style="color:inherit; transition: transform 0.2s, box-shadow 0.2s;">
                        <div style="height:140px;background:var(--bg);display:flex;align-items:center;justify-content:center">
                            @if ($p->foto_produk)
                                <img src="{{ Storage::url($p->foto_produk) }}" alt="{{ $p->nama_produk }}" style="max-height:100%;max-width:100%;object-fit:contain">
                            @else
                                <div class="text-muted" style="font-size:32px">📷</div>
                            @endif
                        </div>
                        <div class="p-3 d-flex flex-column">
                            <div class="fw-semibold text-truncate mb-1" style="font-size:13.5px">{{ $p->nama_produk }}</div>
                            <div class="fw-bold mb-3" style="color:var(--red-strong)">Rp {{ number_format($p->harga, 0, ',', '.') }}</div>
                            <div class="mt-auto">
                                @if ($p->jumlah_produk > 5)
                                    <span class="rl-pill green" style="font-size:10px">Tersedia</span>
                                @elseif ($p->jumlah_produk > 0)
                                    <span class="rl-pill amber" style="font-size:10px">Stok Terbatas</span>
                                @else
                                    <span class="rl-pill red" style="font-size:10px">Habis Terjual</span>
                                @endif
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
        <style>
            .rl-carousel::-webkit-scrollbar { display: none; }
        </style>
    </section>
</x-layouts.public>
