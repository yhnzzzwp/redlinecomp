<x-layouts.public active="Home" title="Hardware & Servis Komputer">
    @php
        $rp = \App\Support\Uang::rupiah(...);
    @endphp

    <section class="rl-hero text-center">
        <div class="rl-kicker mb-3">Redline Komputer <b>·</b> Salatiga</div>
        <h1 class="rl-hero-title">Tembus Batas<br><i>Performa.</i></h1>
        <p class="rl-hero-desc">Hardware pilihan yang diuji satu per satu, rakitan presisi, dan servis dengan estimasi biaya di muka. Dari workstation harian sampai mesin gaming yang digeber sampai garis merah.</p>
        <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap px-3">
            <a href="#katalog" class="btn-redline rl-btn-lg">Jelajahi Katalog</a>
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

    <section id="katalog" class="rl-body rl-container-lg pt-4 pb-5">
        <div class="text-center mb-2" data-reveal>
            <div class="rl-kicker mb-1">Spec-sheet <b>lengkap</b></div>
            <h2 class="rl-title-lg mb-1">Katalog Produk</h2>
            <p class="rl-page-desc mb-0">Temukan komponen dan periferal terbaik untuk kebutuhan PC Anda.</p>
        </div>

        <div class="row g-4">

            <div class="col-lg-3">
                <div class="rl-card p-4 rl-filter-sticky">
                    <button type="button" class="btn-ghost rl-filter-toggle" data-filter-toggle
                            aria-controls="filter-katalog" aria-expanded="false">
                        <span class="d-inline-flex align-items-center gap-2">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                            Filter Produk
                        </span>
                        <svg class="chev" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                    <h3 class="rl-section-title d-none d-lg-block">Filter Produk</h3>
                    <div id="filter-katalog" class="rl-filter-body">
                    <form method="GET" action="{{ route('landing') }}">
                        <div class="rl-form-group">
                            <label class="rl-label" for="f-cari">Cari Nama</label>
                            <input id="f-cari" type="text" name="cari" value="{{ $cari }}" placeholder="Misal: RTX 4090"
                                   class="rl-input">
                        </div>
                        <div class="rl-form-group">
                            <label class="rl-label" for="f-kategori">Kategori</label>
                            <select id="f-kategori" name="kategori" class="rl-select">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}" @selected($kategori_aktif == $k->id)>{{ $k->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-redline w-100 py-2">Terapkan Filter</button>
                        </div>
                        @if ($cari || $kategori_aktif)
                            <div class="mt-2 text-center">
                                <a href="{{ route('landing') }}#katalog" class="rl-text-sm text-decoration-none">Reset Filter</a>
                            </div>
                        @endif
                    </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="row g-4">
                    @forelse ($produk as $p)
                        <div class="col-md-6 col-xl-4" data-reveal style="--reveal-d: {{ ($loop->index % 3) * 80 }}ms">
                            <div class="rl-card h-100 overflow-hidden d-flex flex-column">
                                <div class="p-3 d-flex flex-column flex-fill">
                                    <div class="rl-text-caption mb-1">{{ $p->kategori?->nama_kategori ?? 'Umum' }} &middot; <span class="rl-mono">{{ $p->sku }}</span></div>
                                    <h3 class="rl-catalogue-title mb-2"><a href="{{ route('catalogue.show', $p) }}" class="text-decoration-none text-dark">{{ $p->nama_produk }}</a></h3>
                                    <p class="rl-text-sm rl-text-muted mb-0">{{ Str::limit($p->deskripsi_produk, 90) ?: 'Belum ada deskripsi untuk produk ini.' }}</p>

                                    <div class="mt-auto pt-3">
                                        @php
                                            $waClean = preg_replace('/[^0-9]/', '', (string) config('redline.wa_number'));
                                            if (str_starts_with($waClean, '0')) { $waClean = '62' . substr($waClean, 1); }
                                            $pesanWa = urlencode("Halo Redline, saya ingin bertanya tentang produk:\n\n*{$p->nama_produk}*\nSKU: {$p->sku}");
                                            $waLink = "https://wa.me/{$waClean}?text={$pesanWa}";
                                        @endphp
                                        <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="btn-redline w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                            Tanya via WhatsApp
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="rl-card rl-empty-state">
                                <h4>Produk Tidak Ditemukan</h4>
                                <p class="rl-text-sm mb-0">Maaf, tidak ada produk yang cocok dengan kriteria filter Anda.</p>
                                <a href="{{ route('landing') }}#katalog" class="btn-ghost mt-3 d-inline-block">Reset Filter</a>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($produk->hasPages())
                    <div class="mt-4">{{ $produk->fragment('katalog')->links() }}</div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.public>
