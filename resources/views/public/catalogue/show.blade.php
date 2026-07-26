<x-layouts.public active="Catalogue" :title="$produk->nama_produk">
    @php
        $rp = \App\Support\Uang::rupiah(...);
        $stok = (int) $produk->jumlah_produk;
    @endphp

    <div class="rl-body rl-container-md pt-5">
        <a href="{{ route('landing') }}#katalog" class="rl-back-link mb-4">&larr; Kembali ke Katalog</a>

        <div class="row g-4 mb-5">
            <div class="col-md-8 mx-auto" data-reveal style="--reveal-d: 120ms">
                <div class="d-flex flex-column h-100">
                    <div class="rl-text-caption mb-2">
                        {{ $produk->kategori?->nama_kategori ?? 'Umum' }} &middot; SKU: <span class="tnum">{{ $produk->sku ?? '—' }}</span>
                    </div>

                    <h1 class="rl-page-title mb-2">{{ $produk->nama_produk }}</h1>
                    <div class="rl-text-total-lg mb-3">{{ $rp($produk->harga) }}</div>

                    <div class="mb-4">
                        @if ($stok > 5)
                            <span class="rl-pill green">Stok Tersedia</span>
                        @elseif ($stok > 0)
                            <span class="rl-pill amber">Stok Terbatas &middot; {{ $stok }} unit</span>
                        @else
                            <span class="rl-pill red">Stok Habis</span>
                        @endif
                    </div>

                    <div class="rl-spec-grid mb-4">
                        <div class="rl-spec"><div class="rl-spec__label">Kategori</div><div class="rl-spec__value">{{ $produk->kategori?->nama_kategori ?? 'Umum' }}</div></div>
                        <div class="rl-spec"><div class="rl-spec__label">SKU</div><div class="rl-spec__value tnum">{{ $produk->sku ?? '—' }}</div></div>
                        <div class="rl-spec"><div class="rl-spec__label">Ketersediaan</div><div class="rl-spec__value">{{ $stok > 0 ? $stok.' unit' : 'Habis' }}</div></div>
                        <div class="rl-spec"><div class="rl-spec__label">Garansi</div><div class="rl-spec__value">Resmi</div></div>
                    </div>

                    <div class="mb-4 rl-text-desc">{{ $produk->deskripsi_produk ?: 'Belum ada deskripsi untuk produk ini.' }}</div>

                    <div class="mt-auto">
                        @if ($stok > 0)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="btn-redline d-inline-flex align-items-center justify-content-center gap-2 w-100 py-3">
                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                Pesan via WhatsApp
                            </a>
                        @else
                            <button class="btn-ghost d-inline-flex align-items-center justify-content-center gap-2 w-100 py-3 rl-cursor-not-allowed" disabled>
                                Pesan via WhatsApp (Stok Habis)
                            </button>
                        @endif
                        <p class="rl-text-sm rl-text-muted text-center mt-3 mb-0">Transaksi dilakukan di luar sistem. Hubungi admin via WhatsApp untuk konfirmasi pesanan.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rl-card p-4 mb-5" data-reveal>
            @php
                $trust = [
                    ['Garansi Resmi', '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>'],
                    ['100% Original', '<path d="m9 11 3 3 8-8"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/>'],
                    ['Konsultasi Gratis', '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'],
                ];
            @endphp
            <div class="rl-trust">
                @foreach ($trust as $t)
                    <div class="rl-trust__item">
                        <span class="rl-trust__ico"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $t[1] !!}</svg></span>
                        {{ $t[0] }}
                    </div>
                @endforeach
            </div>
        </div>

        @php
            $lainnya = \App\Models\Produk::where('show_katalog', true)->where('id', '!=', $produk->id)->latest()->limit(8)->get();
        @endphp
        @if ($lainnya->isNotEmpty())
            <section class="mb-5" data-reveal>
                <h2 class="rl-title-lg mb-3">Produk Lainnya</h2>
                <div class="d-flex gap-3 overflow-auto pb-4 rl-carousel">
                    @foreach ($lainnya as $p)
                        <div class="rl-carousel-item">
                            <a href="{{ route('catalogue.show', $p) }}" class="rl-card h-100 overflow-hidden d-block text-decoration-none text-dark">
                                <div class="p-3">
                                    <div class="fw-semibold text-truncate mb-1 rl-text-sm">{{ $p->nama_produk }}</div>
                                    <p class="rl-text-sm rl-text-muted text-truncate mb-2">{{ $p->deskripsi_produk ?: 'Belum ada deskripsi untuk produk ini.' }}</p>
                                    <div class="fw-bold rl-text-total">{{ $rp($p->harga) }}</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.public>
