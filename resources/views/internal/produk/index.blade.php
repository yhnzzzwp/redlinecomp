<x-layouts.app active="produk" title="Product Inventory">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Product Inventory</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">Kelola stok hardware &mdash; {{ number_format($total, 0, ',', '.') }} produk.</p>
        </div>
        <a href="{{ route('produk.create') }}" class="btn-redline">+ Tambah Produk</a>
    </div>

    @if($lowStockCount > 0)
        <div class="rl-card p-3 mb-3 d-flex align-items-center gap-3" style="border-left: 4px solid var(--amber); background-color: #fff9f0;">
            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--amber); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">!</div>
            <div>
                <h3 style="font-size: 14px; margin: 0; font-weight: bold; color: #b45309;">Peringatan Stok Tipis</h3>
                <p style="font-size: 13px; margin: 0; color: #78350f;">Ada {{ $lowStockCount }} produk yang stoknya menipis atau habis. Segera lakukan restock.</p>
            </div>
        </div>
    @endif

    <div class="rl-card">
        <form method="GET" class="d-flex align-items-center gap-2 p-3">
            <div class="rl-search" style="max-width:320px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama atau SKU…"
                       class="border-0 bg-transparent w-100" style="outline:none;font-size:13px">
            </div>
            <button class="btn-ghost btn-sm" type="submit">Cari</button>
            @if ($cari !== '')
                <a href="{{ route('produk.index') }}" class="text-muted text-decoration-none" style="font-size:12.5px">Reset</a>
            @endif
        </form>

        <table class="rl-table">
            <thead>
                <tr>
                    <th>Produk</th><th>Kategori</th><th style="text-align:right">Harga</th>
                    <th>Stok</th><th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($produk as $p)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($p->foto_produk)
                                    <img src="{{ asset('storage/'.$p->foto_produk) }}" alt="" style="width:44px;height:44px;border-radius:8px;object-fit:cover">
                                @else
                                    <div style="width:44px;height:44px;border-radius:8px;background:linear-gradient(135deg,#1b1e23,#3a3e45)"></div>
                                @endif
                                <div>
                                    <div class="fw-semibold" style="font-size:13px">{{ $p->nama_produk }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $p->sku ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $p->kategori?->nama_kategori ?? '—' }}</td>
                        <td style="text-align:right" class="tnum fw-semibold">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                        <td>
                            @php 
                                $s = $p->statusStok(); 
                                $kritis = config('redline.stok_kritis', 5);
                                $isRed = $p->jumlah_produk <= $kritis;
                            @endphp
                            <span class="rl-pill {{ $isRed ? 'red' : ($s === 'Low Stock' ? 'amber' : 'green') }}">{{ $s }} ({{ $p->jumlah_produk }})</span>
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('produk.edit', $p) }}" class="btn-ghost btn-sm">Edit</a>
                            <form method="POST" action="{{ route('produk.destroy', $p) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus produk &quot;{{ $p->nama_produk }}&quot;? Tindakan ini permanen.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm" style="color:var(--red-strong)">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">
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
