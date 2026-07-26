<x-layouts.app active="produk" title="Manajemen Produk">
    <div x-data="{ showImport: false }">
        <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h1 class="rl-page-title mb-1">Manajemen Produk</h1>
                <p class="rl-page-desc mb-0">Kelola stok &amp; katalog hardware toko &mdash; Total {{ number_format($total, 0, ',', '.') }} produk.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="{{ route('produk.export') }}" class="btn-ghost text-decoration-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Ekspor Excel
                </a>
                <button type="button" class="btn-ghost" @click="showImport = !showImport">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Impor Excel
                </button>
                <a href="{{ route('produk.create') }}" class="btn-redline">+ Tambah Produk</a>
            </div>
        </div>

        {{-- Panel Impor Excel --}}
        <div x-show="showImport || {{ ($errors->has('file_excel') || session('import_baris_gagal')) ? 'true' : 'false' }}" x-cloak class="rl-card p-4 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h3 class="rl-section-title mb-0">Impor Data Produk via Excel</h3>
                <a href="{{ route('produk.template') }}" class="btn-ghost rl-btn-sm text-decoration-none">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Unduh Template
                </a>
            </div>
            <p class="rl-text-xs text-muted mb-3">
                Alur: <b>Ekspor Excel</b> &rarr; sesuaikan data di Excel &rarr; unggah kembali di sini (format <code>.xlsx</code>).
                Kolom: <code>nama_produk</code>, <code>sku</code>, <code>kategori</code>, <code>harga</code>, <code>harga_modal</code>, <code>jumlah_produk</code>, <code>deskripsi</code>.
                SKU yang cocok akan memperbarui produk; bila ada baris bermasalah, seluruh impor dibatalkan.
            </p>

            @if ($errors->has('file_excel'))
                <div class="rl-alert rl-alert--error mb-3">{{ $errors->first('file_excel') }}</div>
            @endif
            @if (session('import_baris_gagal'))
                <div class="rl-card p-3 mb-3 rl-border-light" style="max-height:180px;overflow-y:auto">
                    <div class="rl-text-xs fw-bold text-danger mb-2">Perbaiki baris berikut lalu unggah ulang — belum ada data yang diimpor:</div>
                    <ul class="rl-text-xs text-muted mb-0 ps-3">
                        @foreach (session('import_baris_gagal') as $galat)
                            <li>{{ $galat }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('produk.import') }}" enctype="multipart/form-data" class="d-flex align-items-center gap-2 flex-wrap">
                @csrf
                <div class="flex-grow-1">
                    <label for="file_excel" class="visually-hidden">File Excel</label>
                    <input type="file" id="file_excel" name="file_excel" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required class="rl-input w-100">
                </div>
                <button type="submit" class="btn-redline">Proses Impor</button>
                <button type="button" class="btn-ghost" @click="showImport = false">Batal</button>
            </form>
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
                                <div>
                                    <div class="fw-semibold rl-text-sm">{{ $p->nama_produk }}</div>
                                    <div class="rl-text-muted rl-text-xs rl-mono">SKU: {{ $p->sku ?? '—' }}</div>
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
                            <a href="{{ route('stok.mutasi', ['produk_id' => $p->id]) }}" class="btn-ghost btn-sm me-1" aria-label="Riwayat stok {{ $p->nama_produk }}">Riwayat</a>
                            <a href="{{ route('produk.edit', $p) }}" class="btn-ghost btn-sm me-1" aria-label="Edit {{ $p->nama_produk }}">Edit</a>
                            <form method="POST" action="{{ route('produk.destroy', $p) }}" class="d-inline"
                                  x-data @submit.prevent='if (confirm("Hapus produk " + @js($p->nama_produk) + "? Tindakan ini permanen.")) $el.submit()'>
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
</div>
</x-layouts.app>
