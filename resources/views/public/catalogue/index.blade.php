<x-layouts.public active="Catalogue" title="Katalog Produk">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div style="background:var(--navy);color:#fff;padding:60px 34px 40px;text-align:center">
        <h1 class="fw-bold mb-3" style="font-size:32px;letter-spacing:-1px">Katalog Produk</h1>
        <p class="text-muted mx-auto" style="max-width:500px;font-size:15px;color:#a9b7c7!important">Jelajahi koleksi hardware dan periferal premium kami. Temukan komponen terbaik untuk kebutuhan PC Anda.</p>
    </div>

    <div class="rl-body mx-auto" style="max-width:1200px">
        <div class="row g-4">
            {{-- Filter Sidebar --}}
            <div class="col-lg-3">
                <div class="rl-card p-4 sticky-top" style="top:20px">
                    <h3 class="fw-bold mb-3" style="font-size:16px">Filter Produk</h3>
                    <form method="GET" action="{{ route('catalogue') }}">
                        <div class="mb-3">
                            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Cari Nama</label>
                            <input type="text" name="cari" value="{{ $cari }}" placeholder="Misal: RTX 4090"
                                   class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Kategori</label>
                            <select name="kategori" class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategori as $k)
                                    <option value="{{ $k->id }}" @selected($kategori_aktif == $k->id)>{{ $k->nama_kategori }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Harga Minimum</label>
                            <input type="number" name="harga_min" value="{{ $harga_min }}" min="0" placeholder="Rp"
                                   class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
                        </div>
                        <div class="mb-4">
                            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Harga Maksimum</label>
                            <input type="number" name="harga_max" value="{{ $harga_max }}" min="0" placeholder="Rp"
                                   class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-redline w-100" style="padding:9px 13px">Terapkan Filter</button>
                        </div>
                        @if ($cari || $kategori_aktif || $harga_min || $harga_max)
                            <div class="mt-2 text-center">
                                <a href="{{ route('catalogue') }}" class="text-muted" style="font-size:12px">Reset Filter</a>
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Katalog Grid --}}
            <div class="col-lg-9">
                <div class="row g-4">
                    @forelse ($produk as $p)
                        <div class="col-md-6 col-xl-4">
                            <div class="rl-card h-100 overflow-hidden d-flex flex-column" style="transition:transform 0.2s, box-shadow 0.2s">
                                <div style="height:180px;background:var(--bg);display:flex;align-items:center;justify-content:center">
                                    @if ($p->foto_produk)
                                        <img src="{{ Storage::url($p->foto_produk) }}" alt="{{ $p->nama_produk }}" style="max-height:100%;max-width:100%;object-fit:contain">
                                    @else
                                        <div class="text-muted" style="font-size:32px">📷</div>
                                    @endif
                                </div>
                                <div class="p-3 d-flex flex-column flex-fill">
                                    <div class="text-muted mb-1" style="font-size:11.5px">{{ $p->kategori->nama_kategori }} &middot; {{ $p->sku }}</div>
                                    <h3 class="fw-bold mb-2" style="font-size:15px;line-height:1.4"><a href="{{ route('catalogue.show', $p) }}" class="text-decoration-none text-dark">{{ $p->nama_produk }}</a></h3>
                                    
                                    <div class="mt-auto pt-3">
                                        <div class="fw-bold tnum mb-2" style="font-size:18px;color:var(--red-strong)">{{ $rp($p->harga) }}</div>
                                        <div class="d-flex align-items-center justify-content-between">
                                            @if ($p->jumlah_produk > 0)
                                                <span class="rl-pill green" style="font-size:10px">Stok: {{ $p->jumlah_produk }}</span>
                                                <a href="{{ route('catalogue.show', $p) }}" class="btn-redline btn-sm px-3 py-1" style="font-size:12px;border-radius:6px">Detail</a>
                                            @else
                                                <span class="rl-pill red" style="font-size:10px">Habis Terjual</span>
                                                <a href="{{ route('catalogue.show', $p) }}" class="btn-ghost btn-sm px-3 py-1" style="font-size:12px;border-radius:6px">Lihat</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="rl-card p-5 text-center text-muted">
                                <h4 class="fw-bold mb-2">Produk Tidak Ditemukan</h4>
                                <p class="mb-0" style="font-size:14px">Maaf, tidak ada produk yang cocok dengan kriteria filter Anda.</p>
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
