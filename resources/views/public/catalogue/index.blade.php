<x-layouts.public active="Catalogue" title="Katalog Produk">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-public-header">
        <div class="rl-kicker mb-2">Spec-sheet <b>lengkap</b></div>
        <h1 class="rl-page-title">Katalog Produk</h1>
        <p class="rl-page-desc">Jelajahi koleksi hardware dan periferal premium kami. Temukan komponen terbaik untuk kebutuhan PC Anda.</p>
        <div class="rl-ticks rl-ticks--dark"></div>
    </div>

    <div class="rl-body rl-container-lg">
        <div class="row g-4">
            {{-- Filter Sidebar --}}
            <div class="col-lg-3">
                <div class="rl-card p-4 sticky-top rl-pos-bill">
                    <h3 class="rl-section-title">Filter Produk</h3>
                    <form method="GET" action="{{ route('catalogue') }}">
                        <div class="rl-form-group">
                            <label class="rl-label">Cari Nama</label>
                            <input type="text" name="cari" value="{{ $cari }}" placeholder="Misal: RTX 4090"
                                   class="rl-input">
                        </div>
                        <div class="rl-form-group">
                            <label class="rl-label">Kategori</label>
                            <select name="kategori" class="rl-select">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}" @selected($kategori_aktif == $k->id)>{{ $k->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="rl-form-group">
                            <label class="rl-label">Harga Minimum</label>
                            <input type="number" name="harga_min" value="{{ $harga_min }}" min="0" placeholder="Rp"
                                   class="rl-input">
                        </div>
                        <div class="rl-form-group mb-4">
                            <label class="rl-label">Harga Maksimum</label>
                            <input type="number" name="harga_max" value="{{ $harga_max }}" min="0" placeholder="Rp"
                                   class="rl-input">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-redline w-100 py-2">Terapkan Filter</button>
                        </div>
                        @if ($cari || $kategori_aktif || $harga_min || $harga_max)
                            <div class="mt-2 text-center">
                                <a href="{{ route('catalogue') }}" class="rl-text-sm text-decoration-none">Reset Filter</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Katalog Grid --}}
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
                                <a href="{{ route('catalogue') }}" class="btn-ghost mt-3 d-inline-block">Reset Filter</a>
                            </div>
                        </div>
                    @endforelse
                </div>

                @if ($produk->hasPages())
                    <div class="mt-4">{{ $produk->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.public>
