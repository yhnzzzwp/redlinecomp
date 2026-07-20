<x-layouts.app active="dashboard" title="Daftar Transaksi">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('dashboard') }}" class="text-muted text-decoration-none" style="font-size:13px">&larr; Dashboard</a>
    </div>
    
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mt-2 mb-3">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Daftar Transaksi</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">Riwayat seluruh transaksi di Redline Komputer.</p>
        </div>
    </div>

    <div class="rl-card p-3 mb-3">
        <form method="GET" action="{{ route('transaksi.index') }}" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari kode nota..."
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
            </div>
            <div class="col-md-3">
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
            </div>
            <div class="col-md-3">
                <select name="jenis" class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13.5px">
                    <option value="">Semua Jenis Item</option>
                    @foreach (\App\Enums\TipeItem::cases() as $t)
                        <option value="{{ $t->value }}" @selected($jenis === $t->value)>{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn-redline w-100" style="padding:9px 13px">Filter</button>
                @if ($cari || $tanggal || $jenis)
                    <a href="{{ route('transaksi.index') }}" class="btn-ghost" style="padding:9px 13px">Reset</a>
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
                    <th style="text-align:right">Total</th>
                    <th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaksi as $t)
                    <tr>
                        <td>
                            <div class="fw-semibold tnum">#{{ $t->kode_nota }}</div>
                            <div class="text-muted" style="font-size:11.5px">{{ $t->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td>{{ $t->pegawai?->nama_pegawai ?? '—' }}</td>
                        <td>
                            <div style="font-size:12.5px">
                                @foreach ($t->items->take(2) as $item)
                                    <div>{{ $item->jumlah }}x {{ $item->nama_item }}</div>
                                @endforeach
                                @if ($t->items->count() > 2)
                                    <div class="text-muted" style="font-size:11px">+ {{ $t->items->count() - 2 }} item lainnya</div>
                                @endif
                            </div>
                        </td>
                        <td style="text-align:right">
                            <div class="fw-bold tnum">{{ $rp($t->total) }}</div>
                            <span class="rl-pill {{ $t->metode_bayar === 'Tunai' ? 'green' : 'blue' }}" style="font-size:9px">{{ $t->metode_bayar }}</span>
                        </td>
                        <td style="text-align:right">
                            <a href="{{ route('pos.nota', $t) }}" target="_blank" class="btn-ghost btn-sm">Lihat Nota</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center p-4 text-muted">Data transaksi tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($transaksi->hasPages())
        <div class="mt-3">{{ $transaksi->links() }}</div>
    @endif
</x-layouts.app>
