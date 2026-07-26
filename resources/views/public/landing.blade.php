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

    {{-- Katalog langsung di beranda --}}
    <section id="katalog" class="rl-body rl-container-lg pt-4 pb-5">
        <div class="text-center mb-2" data-reveal>
            <div class="rl-kicker mb-1">Spec-sheet <b>lengkap</b></div>
            <h2 class="rl-title-lg mb-1">Katalog Produk</h2>
            <p class="rl-page-desc mb-0">Temukan komponen dan periferal terbaik untuk kebutuhan PC Anda.</p>
        </div>

        <div class="row g-4">
            {{-- Filter (dilipat di layar kecil) --}}
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
                        <div class="rl-form-group">
                            <label class="rl-label" for="f-min">Harga Minimum</label>
                            <input id="f-min" type="number" name="harga_min" value="{{ $harga_min }}" min="0" placeholder="Rp"
                                   class="rl-input">
                        </div>
                        <div class="rl-form-group mb-4">
                            <label class="rl-label" for="f-max">Harga Maksimum</label>
                            <input id="f-max" type="number" name="harga_max" value="{{ $harga_max }}" min="0" placeholder="Rp"
                                   class="rl-input">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-redline w-100 py-2">Terapkan Filter</button>
                        </div>
                        @if ($cari || $kategori_aktif || $harga_min || $harga_max)
                            <div class="mt-2 text-center">
                                <a href="{{ route('landing') }}#katalog" class="rl-text-sm text-decoration-none">Reset Filter</a>
                            </div>
                        @endif
                    </form>
                    </div>
                </div>
            </div>

            {{-- Grid produk --}}
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
                                        <div class="rl-text-total mb-2">{{ $rp($p->harga) }}</div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            @if ($p->jumlah_produk > 5)
                                                <span class="rl-pill green">Tersedia</span>
                                                <a href="{{ route('catalogue.show', $p) }}" class="btn-redline rl-btn-sm">Detail</a>
                                            @elseif ($p->jumlah_produk > 0)
                                                <span class="rl-pill amber">Stok Terbatas</span>
                                                <a href="{{ route('catalogue.show', $p) }}" class="btn-redline rl-btn-sm">Detail</a>
                                            @else
                                                <span class="rl-pill red">Habis Terjual</span>
                                                <a href="{{ route('catalogue.show', $p) }}" class="btn-ghost rl-btn-sm">Lihat</a>
                                            @endif
                                        </div>
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
