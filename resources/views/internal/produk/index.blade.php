<x-layouts.app active="produk" title="Manajemen Produk">
    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="rl-page-title mb-1">Manajemen Produk</h1>
            <p class="rl-page-desc mb-0">Kelola stok &amp; katalog hardware toko &mdash; Total {{ number_format($total, 0, ',', '.') }} produk.</p>
        </div>
        <a href="{{ route('produk.create') }}" class="btn-redline">+ Tambah Produk</a>
    </div>

    @if($lowStockCount > 0)
        <div class="rl-alert rl-alert--error mb-3 d-flex align-items-center gap-3">
            <div class="fw-bold fs-4"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Perhatian"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div>
            <div>
                <h3 class="fw-bold m-0 rl-text-sm">Peringatan Stok Tipis</h3>
                <p class="m-0 rl-text-xs">Ada {{ $lowStockCount }} produk yang stoknya menipis atau habis. Segera lakukan restock.</p>
            </div>
        </div>
    @endif

    <div class="rl-card">
        <form method="GET" class="d-flex align-items-center gap-2 p-3 flex-wrap border-bottom">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <label for="cari" class="visually-hidden">Cari produk</label>
                <input type="text" id="cari" name="cari" value="{{ $cari }}" placeholder="Cari nama produk atau SKU…"
                       class="rl-input rl-input--sm rl-input--search flex-grow-1">
            </div>
            <button class="btn-ghost btn-sm" type="submit">Cari</button>
            @if ($cari !== '')
                <a href="{{ route('produk.index') }}" class="text-muted text-decoration-none rl-text-xs ms-1">Reset</a>
            @endif
        </form>

        <table class="rl-table align-middle">
            <thead>
                <tr>
                    <th>Detail Produk &amp; SKU</th>
                    <th>Kategori</th>
                    <th class="text-end">Harga Jual</th>
                    <th>Status &amp; Jumlah Stok</th>
                    <th class="text-end">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($produk as $p)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($p->foto_produk)
                                    <img src="{{ asset('storage/'.$p->foto_produk) }}" alt="{{ $p->nama_produk }}" class="rl-table-thumb">
                                @else
                                    <div class="rl-table-thumb d-flex align-items-center justify-content-center bg-light text-secondary rounded border">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="18" height="12" x="3" y="4" rx="2" ry="2"/><line x1="2" x2="22" y1="20" y2="20"/></svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-semibold rl-text-sm">{{ $p->nama_produk }}</div>
                                    <div class="rl-text-muted rl-text-xs font-monospace">SKU: {{ $p->sku ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border rl-text-xs">{{ $p->kategori?->nama_kategori ?? 'Umum' }}</span>
                        </td>
                        <td class="text-end tnum fw-bold text-danger rl-text-sm">Rp {{ number_format($p->harga, 0, ',', '.') }}</td>
                        <td>
                            @php 
                                $stok = (int) $p->jumlah_produk;
                                $kritis = (int) config('redline.stok_kritis', 5);
                            @endphp
                            @if ($stok === 0)
                                <span class="rl-pill red">Stok Habis (0)</span>
                            @elseif ($stok <= $kritis)
                                <span class="rl-pill amber">Stok Kritis ({{ $stok }})</span>
                            @else
                                <span class="rl-pill green">Tersedia ({{ $stok }} Unit)</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('produk.edit', $p) }}" class="btn-ghost btn-sm me-1" aria-label="Edit {{ $p->nama_produk }}">Edit</a>
                            <form method="POST" action="{{ route('produk.destroy', $p) }}" class="d-inline"
                                  onsubmit="return confirm('Hapus produk &quot;{{ $p->nama_produk }}&quot;? Tindakan ini permanen.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm text-danger" aria-label="Hapus {{ $p->nama_produk }}">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3" style="color: #E9E9EC;">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                            <span class="rl-text-sm">{{ $cari !== '' ? 'Tidak ada produk cocok dengan pencarian.' : 'Belum Ada Produk' }}</span>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($produk->hasPages())
            <div class="p-3">{{ $produk->links() }}</div>
        @endif
    </div>
</x-layouts.app>
