<x-layouts.app active="dashboard" title="Daftar Transaksi">
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
            <a href="{{ route('transaksi.export', ['cari' => $cari, 'tanggal' => $tanggal, 'jenis' => $jenis]) }}" class="btn-redline">⭳ Export CSV</a>
        </div>
    </div>

    <div class="rl-card p-3 mb-3">
        <form method="GET" action="{{ route('transaksi.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari kode nota..." class="rl-input w-100">
            </div>
            <div class="col-md-3">
                <input type="date" name="tanggal" value="{{ $tanggal }}" class="rl-input w-100">
            </div>
            <div class="col-md-3">
                <select name="jenis" class="rl-select w-100">
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
                            <div class="rl-text-muted rl-text-xs mb-1">{{ $t->created_at->format('d M Y, H:i') }}</div>
                            @if($t->status === 'Normal')
                                <span class="rl-pill green rl-text-xs">{{ $t->status }}</span>
                            @else
                                <span class="rl-pill red rl-text-xs">{{ $t->status }}</span>
                            @endif
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
                            <span class="rl-pill {{ $t->metode_bayar === 'Tunai' ? 'green' : 'blue' }} rl-text-xs">{{ $t->metode_bayar }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('pos.nota', $t) }}" target="_blank" class="btn-ghost btn-sm">Lihat Nota</a>
                            @if($t->status === 'Normal')
                                <form method="POST" action="{{ route('transaksi.void', $t) }}" class="d-inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin melakukan Void transaksi ini? Stok produk akan dikembalikan.');">
                                    @csrf
                                    <button type="submit" class="btn-ghost btn-sm text-danger">Void</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center p-4 rl-text-muted">Data transaksi tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transaksi->hasPages())
        <div class="mt-3">{{ $transaksi->links() }}</div>
    @endif
</x-layouts.app>
