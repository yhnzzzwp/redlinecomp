<x-layouts.app active="transaksi" title="Daftar Transaksi">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-page-header">
        <a href="{{ route('dashboard') }}" class="rl-back-link text-decoration-none rl-text-sm">&larr; Dashboard</a>
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mt-2">
            <div>
                <h1 class="rl-page-title mb-1">Daftar Transaksi</h1>
                <p class="rl-page-desc mb-0">Riwayat seluruh transaksi di Redline Komputer.</p>
            </div>
            @if (auth()->user()?->isOwner())
                <a href="{{ route('transaksi.export', ['cari' => $cari, 'tanggal' => $tanggal, 'jenis' => $jenis]) }}" class="btn-redline">⭳ Export CSV</a>
            @endif
        </div>
    </div>

    <div class="rl-card p-3 mb-3">
        <form method="GET" action="{{ route('transaksi.index') }}" class="row g-2">
            <div class="col-md-4">
                <label for="cari" class="visually-hidden">Cari kode nota</label>
                <input type="text" id="cari" name="cari" value="{{ $cari }}" placeholder="Cari kode nota..." class="rl-input w-100">
            </div>
            <div class="col-md-3">
                <label for="tanggal" class="visually-hidden">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" value="{{ $tanggal }}" class="rl-input w-100">
            </div>
            <div class="col-md-3">
                <label for="jenis" class="visually-hidden">Jenis Item</label>
                <select id="jenis" name="jenis" class="rl-select w-100">
                    <option value="">Semua Jenis Item</option>
                    @foreach (\App\Enums\TipeItem::cases() as $t)
                        <option value="{{ $t->value }}" @selected($jenis === $t->value)>{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn-redline w-100">Filter</button>
                @if ($cari || $tanggal || $jenis)
                    <a href="{{ route('transaksi.index') }}" class="btn-ghost">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="rl-card overflow-hidden">
        <table class="rl-table">
            <thead>
                <tr>
                    <th>Nota & Waktu</th>
                    <th>Pegawai</th>
                    <th>Item Transaksi</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaksi as $t)
                    <tr>
                        <td>
                            <div class="fw-semibold tnum">#{{ $t->kode_nota }}</div>
                            <div class="rl-text-muted rl-text-xs">{{ $t->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td>{{ $t->pegawai?->nama_pegawai ?? '—' }}</td>
                        <td>
                            <div class="rl-text-sm">
                                @foreach ($t->items->take(2) as $item)
                                    <div>{{ $item->jumlah }}x {{ $item->nama_item }}</div>
                                @endforeach
                                @if ($t->items->count() > 2)
                                    <div class="rl-text-muted rl-text-xs">+ {{ $t->items->count() - 2 }} item lainnya</div>
                                @endif
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="fw-bold tnum">{{ $rp($t->total) }}</div>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('pos.nota', $t) }}" target="_blank" class="btn-ghost btn-sm" aria-label="Lihat atau print nota #{{ $t->kode_nota }}">Lihat Nota</a>
                            @if($t->status === 'Normal')
                                <form method="POST" action="{{ route('transaksi.void', $t) }}" class="d-inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin melakukan Void transaksi ini? Stok produk akan dikembalikan.');">
                                    @csrf
                                    <button type="submit" class="btn-ghost btn-sm text-danger" aria-label="Void transaksi #{{ $t->kode_nota }}">Void</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3" style="color: #E9E9EC;">
                                <path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"></path>
                                <path d="M16 14h-4"></path>
                                <path d="M16 10H8"></path>
                            </svg>
                            <span class="rl-text-sm">Belum Ada Transaksi</span>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transaksi->hasPages())
        <div class="mt-3">{{ $transaksi->links() }}</div>
    @endif
</x-layouts.app>
