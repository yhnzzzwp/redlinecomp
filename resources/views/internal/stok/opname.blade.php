<x-layouts.app active="stok" title="Stok Opname">
    @php($rp = \App\Support\Uang::rupiah(...))

    <div class="rl-page-header">
        <div>
            <h1 class="rl-page-title mb-1">Stok Opname</h1>
            <p class="rl-page-desc mb-0">Cocokkan stok sistem dengan hitungan fisik di rak &mdash; selisih tercatat sebagai mutasi Opname.</p>
        </div>
        <a href="{{ route('stok.mutasi') }}" class="btn-ghost">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
            Riwayat Mutasi
        </a>
    </div>

    <form method="POST" action="{{ route('stok.opname.simpan') }}"
          x-data="{ sistem: @js($produk->pluck('jumlah_produk', 'id')), fisik: {},
                    selisih(id) { const f = this.fisik[id]; if (f === undefined || f === '') return null; return parseInt(f, 10) - this.sistem[id]; },
                    get totalSelisih() { return Object.keys(this.fisik).filter(id => this.selisih(id) !== null && this.selisih(id) !== 0).length; } }">
        @csrf
        <div class="rl-card overflow-hidden">
            <div class="p-3 border-bottom rl-divider-light d-flex align-items-center gap-2 flex-wrap">
                <label class="rl-label mb-0" for="catatan">Catatan opname</label>
                <input type="text" id="catatan" name="catatan" maxlength="255" placeholder="mis. opname akhir bulan"
                       class="rl-input rl-input--sm" style="max-width:320px">
                <div class="ms-auto rl-text-sm text-muted">
                    Produk berselisih: <b class="tnum" x-text="totalSelisih">0</b>
                </div>
                <button type="submit" class="btn-redline" @click="return totalSelisih === 0 ? confirm('Tidak ada selisih terisi. Simpan opname kosong?') : confirm('Simpan opname? Stok sistem akan disesuaikan dengan angka fisik.')">
                    Simpan Opname
                </button>
            </div>

            <table class="rl-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th class="text-end">Stok Sistem</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($produk as $p)
                        <tr>
                            <td>
                                <div class="fw-semibold rl-text-sm">{{ $p->nama_produk }}</div>
                                <div class="rl-text-muted rl-text-xs rl-mono">{{ $p->sku ?? '—' }} &middot; {{ $p->kategori?->nama_kategori ?? 'Umum' }}</div>
                            </td>
                            <td class="text-end tnum fw-semibold">{{ $p->jumlah_produk }}</td>
                            <td class="text-end">
                                <label class="visually-hidden" for="stok-{{ $p->id }}">Stok fisik {{ $p->nama_produk }}</label>
                                <input type="number" id="stok-{{ $p->id }}" name="stok[{{ $p->id }}]" min="0" max="1000000"
                                       inputmode="numeric" placeholder="{{ $p->jumlah_produk }}"
                                       x-model="fisik[{{ $p->id }}]"
                                       class="rl-input rl-input--sm text-end tnum d-inline-block" style="max-width:110px">
                            </td>
                            <td class="text-end tnum fw-bold"
                                :class="{ 'text-danger': selisih({{ $p->id }}) < 0, 'text-success': selisih({{ $p->id }}) > 0 }"
                                x-text="selisih({{ $p->id }}) === null ? '—' : (selisih({{ $p->id }}) > 0 ? '+' + selisih({{ $p->id }}) : selisih({{ $p->id }}))">—</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 rl-text-sm text-muted">Belum ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="rl-text-xs text-muted mt-2 mb-0">Kosongkan kolom stok fisik untuk produk yang tidak dihitung &mdash; hanya baris terisi yang diproses.</p>
    </form>
</x-layouts.app>
