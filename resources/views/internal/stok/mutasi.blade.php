<x-layouts.app active="stok" title="Riwayat Mutasi Stok">
    <div class="rl-page-header">
        <div>
            <h1 class="rl-page-title mb-1">Riwayat Mutasi Stok</h1>
            <p class="rl-page-desc mb-0">
                Jejak setiap pergerakan barang: penjualan, void, penyesuaian, opname, dan impor.
                @if ($produkFilter)
                    &mdash; difilter: <b>{{ $produkFilter->nama_produk }}</b> <a href="{{ route('stok.mutasi') }}" class="rl-text-sm">(hapus filter)</a>
                @endif
            </p>
        </div>
        <a href="{{ route('stok.opname') }}" class="btn-redline">Mulai Opname</a>
    </div>

    <div class="rl-card overflow-hidden">
        <form method="GET" class="d-flex align-items-center gap-2 p-3 flex-wrap border-bottom rl-divider-light">
            <label class="visually-hidden" for="cari">Cari produk</label>
            <input type="text" id="cari" name="cari" value="{{ $cari }}" placeholder="Cari nama produk / SKU…"
                   class="rl-input rl-input--sm rl-input--search flex-grow-1" style="max-width:320px">
            <label class="visually-hidden" for="tipe">Tipe mutasi</label>
            <select id="tipe" name="tipe" class="rl-select rl-select--sm" style="max-width:180px">
                <option value="">Semua tipe</option>
                @foreach ($tipeList as $t)
                    <option value="{{ $t->value }}" @selected($tipeAktif === $t->value)>{{ $t->value }}</option>
                @endforeach
            </select>
            <button class="btn-ghost btn-sm" type="submit">Terapkan</button>
            @if ($cari !== '' || $tipeAktif)
                <a href="{{ route('stok.mutasi') }}" class="text-muted text-decoration-none rl-text-xs">Reset</a>
            @endif
        </form>

        <table class="rl-table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Produk</th>
                    <th>Tipe</th>
                    <th class="text-end">Perubahan</th>
                    <th>Keterangan</th>
                    <th>Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($mutasi as $m)
                    <tr>
                        <td class="tnum rl-text-sm text-muted">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="fw-semibold rl-text-sm">{{ $m->produk?->nama_produk ?? '—' }}</div>
                            <div class="rl-text-muted rl-text-xs rl-mono">{{ $m->produk?->sku ?? '' }}</div>
                        </td>
                        <td><span class="rl-pill {{ $m->tipe->warna() }}">{{ $m->tipe->value }}</span></td>
                        <td class="text-end tnum">
                            <span class="text-muted">{{ $m->jumlah_sebelum }}</span>
                            &rarr; <b>{{ $m->jumlah_sesudah }}</b>
                            <span class="fw-bold {{ $m->selisih < 0 ? 'text-danger' : 'text-success' }}">({{ $m->selisih > 0 ? '+' : '' }}{{ $m->selisih }})</span>
                        </td>
                        <td class="rl-text-sm text-muted">{{ $m->keterangan ?? '—' }}</td>
                        <td class="rl-text-sm">{{ $m->pegawai?->nama_pegawai ?? 'Sistem' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-5 rl-text-sm text-muted">Belum ada mutasi stok tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($mutasi->hasPages())
        <div class="mt-3">{{ $mutasi->links() }}</div>
    @endif
</x-layouts.app>
