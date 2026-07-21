<x-layouts.app active="produk" title="Manajemen Produk">
    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="rl-page-title mb-1">Manajemen Produk</h1>
            <p class="rl-page-desc mb-0">Kelola stok hardware &mdash; {{ number_format($total, 0, ',', '.') }} produk.</p>
        </div>
        <a href="{{ route('produk.create') }}" class="btn-redline">+ Tambah Produk</a>
    </div>

    @if($lowStockCount > 0)
        <div class="rl-alert rl-alert--error mb-3 d-flex align-items-center gap-3">
            <div class="fw-bold fs-4">!</div>
            <div>
                <h3 class="fw-bold m-0 rl-text-sm">Peringatan Stok Tipis</h3>
                <p class="m-0 rl-text-xs">Ada {{ $lowStockCount }} produk yang stoknya menipis atau habis. Segera lakukan restock.</p>
            </div>
        </div>
    @endif

    <div class="rl-card">
        <form method="GET" class="d-flex align-items-center gap-2 p-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama atau SKU…"
                       class="rl-input rl-input--sm rl-input--search flex-grow-1">
            </div>
            <button class="btn-ghost btn-sm" type="submit">Cari</button>
            @if ($cari !== '')
                <a href="{{ route('produk.index') }}" class="text-muted text-decoration-none rl-text-xs">Reset</a>
            @endif
        </form>

        <table class="rl-table">
            <thead>
                <tr>
                    <th>Produk</th><th>Kategori</th><th class="text-end">Harga</th>
                    <th>Stok</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($produk as $p)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($p->foto_produk)
                                    <img src="{{ asset('storage/'.$p->foto_produk) }}" alt="" class="rl-product-thumb">
                                @else
                                    <div class="rl-product-thumb"></div>
                                @endif
                                <div>
                                    <div class="fw-semibold rl-text-sm">{{ $p->nama_produk }}</div>
                                    <div class="rl-text-muted rl-text-xs">{{ $p->sku ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="rl-text-muted">{{ $p->kategori?->nama_kategori ?? '—' }}</td>
                        <td class="text-end tnum fw-semibold">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                        <td>
                            @php 
                                $s = $p->statusStok(); 
                                $kritis = config('redline.stok_kritis', 5);
                                $isRed = $p->jumlah_produk <= $kritis;
                            @endphp
                            <span class="rl-pill {{ $isRed ? 'red' : ($s === 'Low Stock' ? 'amber' : 'green') }}">{{ $s }} ({{ $p->jumlah_produk }})</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('produk.edit', $p) }}" class="btn-ghost btn-sm">Edit</a>
                            <form method="POST" action="{{ route('produk.destroy', $p) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus produk &quot;{{ $p->nama_produk }}&quot;? Tindakan ini permanen.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm text-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center rl-text-muted py-5">
                        {{ $cari !== '' ? 'Tidak ada produk cocok dengan pencarian.' : 'Belum ada produk.' }}
                    </td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($produk->hasPages())
            <div class="p-3">{{ $produk->links() }}</div>
        @endif
    </div>
</x-layouts.app>
