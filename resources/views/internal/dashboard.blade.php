<x-layouts.app active="dashboard" title="Dashboard">
    @php
        $rp = \App\Support\Uang::rupiah(...);
    @endphp

    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="rl-page-title mb-1">Ringkasan Dashboard</h1>
            <p class="rl-page-desc mb-0">Metrik kinerja real-time Redline Komputer.</p>
        </div>
        @if (auth()->user()?->isOwner())
            <a href="{{ route('analytics.cetak') }}" target="_blank" class="btn-redline text-decoration-none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="rl-icon-16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                Ekspor Laporan
            </a>
        @endif
    </div>

    {{-- KPI bento --}}
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-3 bg-danger bg-opacity-10 text-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </div>
                <div class="rl-kpi__label">Total Penjualan</div>
                <div class="rl-kpi__val tnum">{{ $rp($totalSales) }}</div>
                <div class="rl-text-xs text-muted mt-1">Omzet bersih terakumulasi</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-3 bg-success bg-opacity-10 text-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <div class="rl-kpi__label">Penjualan Hari Ini</div>
                <div class="rl-kpi__val tnum text-success">{{ $rp($todaySales) }}</div>
                <div class="rl-text-xs text-muted mt-1">{{ $todayCount }} transaksi hari ini</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-3 bg-primary bg-opacity-10 text-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M14 6a4 4 0 005 5l-8 8-3-3 6-6a4 4 0 010-4z"/></svg>
                </div>
                <div class="rl-kpi__label">Servis Aktif</div>
                <div class="rl-kpi__val tnum">{{ $activeServices }} <span class="fs-6 fw-normal text-muted">Tiket</span></div>
                <div class="rl-text-xs text-muted mt-1">Dalam proses perbaikan</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-3 bg-warning bg-opacity-10 text-warning">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="20" height="20"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
                </div>
                <div class="rl-kpi__label">Total Produk</div>
                <div class="rl-kpi__val tnum">{{ number_format($totalProducts,0,',','.') }} <span class="fs-6 fw-normal text-muted">Item</span></div>
                <div class="rl-text-xs text-muted mt-1">Tercatat di katalog</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Sales trend --}}
        <div class="col-lg-8">
            <div class="rl-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="rl-section-title mb-1">Tren Penjualan (7 Hari Terakhir)</h3>
                        <p class="rl-text-xs rl-text-muted mb-0">Pendapatan harian dari transaksi normal</p>
                    </div>
                </div>
                @php $max = max(1, $trend->max('total')); @endphp
                <div class="rl-chart-bar-wrap">
                    @foreach ($trend as $t)
                        <div class="rl-chart-bar">
                            <div class="rl-chart-bar__fill rounded-top" title="{{ $t['label'] }}: {{ $rp($t['total']) }}"
                                 style="height:{{ max(6, (int)($t['total']/$max*100)) }}%;"></div>
                            <small class="rl-chart-bar__label">{{ $t['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent transactions --}}
        <div class="col-lg-4">
            <div class="rl-card h-100">
                <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                    <h3 class="rl-section-title mb-0">Transaksi Terakhir</h3>
                    <a href="{{ route('transaksi.index') }}" class="fw-semibold text-decoration-none rl-text-xs text-danger">Lihat Semua &rarr;</a>
                </div>
                <div class="px-2 pb-2">
                    @forelse ($recent as $t)
                        <div class="d-flex align-items-center justify-content-between px-2 py-2 border-bottom rl-divider-light">
                            <div>
                                <div class="fw-semibold rl-text-sm rl-mono">#{{ $t->kode_nota }}</div>
                                <div class="rl-text-muted rl-text-xs">{{ $t->nama_pembeli ?? 'Umum' }} &middot; {{ $t->pegawai?->nama_pegawai ?? 'Kasir' }} &middot; {{ $t->created_at?->format('d M H:i') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold tnum rl-text-sm">{{ $rp($t->total) }}</div>
                                @if(($t->status->value ?? $t->status) === 'Batal')
                                    <span class="rl-pill red rl-text-xs">BATAL</span>
                                @else
                                    <span class="rl-pill green rl-text-xs">LUNAS</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="rl-text-muted text-center py-4 rl-text-sm">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Critical stock --}}
    <div class="rl-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="rl-section-title mb-0">Stok Menipis</h3>
            <a href="{{ route('produk.index') }}" class="fw-semibold text-decoration-none rl-text-sm text-danger">Manajemen Produk &rarr;</a>
        </div>
        <div class="row g-3">
            @forelse ($criticalStock as $p)
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3 p-2 rounded bg-light border">
                        <div class="flex-fill">
                            <div class="fw-semibold rl-text-sm text-truncate" style="max-width: 150px;">{{ $p->nama_produk }}</div>
                            <div class="rl-text-muted rl-text-xs rl-mono">{{ $p->sku ?? '—' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-danger rl-text-sm tnum">{{ $p->jumlah_produk }} Tersisa</div>
                            <div class="rl-text-muted rl-text-xs">{{ $p->statusStok() }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="rl-text-muted mb-0 rl-text-sm">Semua stok aman.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
