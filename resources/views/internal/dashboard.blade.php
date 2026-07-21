<x-layouts.app active="dashboard" title="Dashboard">
    @php
        function rl_rp($n) { return 'Rp '.number_format((int)$n, 0, ',', '.'); }
    @endphp

    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="rl-page-title mb-1">Ringkasan Dashboard</h1>
            <p class="rl-page-desc mb-0">Real-time performance metrics untuk Redline Komputer.</p>
        </div>
        <a href="{{ route('analytics.cetak') }}" target="_blank" class="btn-redline text-decoration-none">⭳ Ekspor Laporan</a>
    </div>

    {{-- KPI bento --}}
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--red-soft);color:var(--red-strong)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </div>
                <div class="rl-kpi__label">Total Penjualan</div>
                <div class="rl-kpi__val tnum">{{ rl_rp($totalSales) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--blue-soft);color:#1d4ed8">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><path d="M14 6a4 4 0 005 5l-8 8-3-3 6-6a4 4 0 010-4z"/></svg>
                </div>
                <div class="rl-kpi__label">Servis Aktif</div>
                <div class="rl-kpi__val tnum">{{ $activeServices }} <span class="fs-6 fw-normal text-muted">Servis</span></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--amber-soft);color:#b06a05">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
                </div>
                <div class="rl-kpi__label">Total Produk</div>
                <div class="rl-kpi__val tnum">{{ number_format($totalProducts,0,',','.') }} <span class="fs-6 fw-normal text-muted">Item</span></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--green-soft);color:#15803d">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-5 6-5s6 1.7 6 5"/></svg>
                </div>
                <div class="rl-kpi__label">Total Pegawai</div>
                <div class="rl-kpi__val tnum">{{ \App\Models\Pegawai::count() }} <span class="fs-6 fw-normal text-muted">Akun</span></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Sales trend --}}
        <div class="col-lg-8">
            <div class="rl-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="rl-section-title mb-1">Tren Penjualan</h3>
                        <p class="rl-text-xs rl-text-muted mb-0">Pendapatan 7 hari terakhir</p>
                    </div>
                </div>
                @php $max = max(1, $trend->max('total')); @endphp
                <div class="rl-chart-bar-wrap">
                    @foreach ($trend as $t)
                        <div class="rl-chart-bar">
                            <div class="rl-chart-bar__fill rounded-top" title="{{ rl_rp($t['total']) }}"
                                 style="height:{{ max(4, (int)($t['total']/$max*100)) }}%;"></div>
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
                    <a href="{{ route('transaksi.index') }}" class="fw-semibold text-decoration-none rl-text-xs" style="color:var(--red-strong)">Lihat Semua &rarr;</a>
                </div>
                <div class="px-2 pb-2">
                    @forelse ($recent as $t)
                        <div class="d-flex align-items-center justify-content-between px-2 py-2 border-bottom rl-divider-light">
                            <div>
                                <div class="fw-semibold rl-text-sm">Nota #{{ $t->kode_nota }}</div>
                                <div class="rl-text-muted rl-text-xs">{{ $t->pegawai?->nama_pegawai }} · {{ $t->created_at->format('d M H:i') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold tnum rl-text-sm">{{ rl_rp($t->total) }}</div>
                                <span class="rl-pill green rl-text-xs">PAID</span>
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
            <a href="{{ route('produk.index') }}" class="fw-semibold text-decoration-none rl-text-sm" style="color:var(--red-strong)">Manajemen Produk &rarr;</a>
        </div>
        <div class="row g-3">
            @forelse ($criticalStock as $p)
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--bg)">
                        <div class="rl-product-thumb"></div>
                        <div class="flex-fill">
                            <div class="fw-semibold rl-text-sm">{{ $p->nama_produk }}</div>
                            <div class="rl-text-muted rl-text-xs">{{ $p->sku }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold {{ $p->jumlah_produk == 0 ? 'text-danger' : '' }} rl-text-sm" style="color:var(--red-strong)">{{ $p->jumlah_produk }} Tersisa</div>
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
