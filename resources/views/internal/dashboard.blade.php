<x-layouts.app active="dashboard" title="Dashboard">
    @php
        function rl_rp($n) { return 'Rp '.number_format((int)$n, 0, ',', '.'); }
    @endphp

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Dashboard Overview</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">Real-time performance metrics untuk Redline Komputer.</p>
        </div>
        <button class="btn-redline">⭳ Export Report</button>
    </div>

    {{-- KPI bento --}}
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--red-soft);color:var(--red-strong)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </div>
                <div class="rl-kpi__label">Total Sales</div>
                <div class="rl-kpi__val tnum">{{ rl_rp($totalSales) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--blue-soft);color:#1d4ed8">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><path d="M14 6a4 4 0 005 5l-8 8-3-3 6-6a4 4 0 010-4z"/></svg>
                </div>
                <div class="rl-kpi__label">Active Services</div>
                <div class="rl-kpi__val tnum">{{ $activeServices }} <span class="fs-6 fw-normal text-muted">Repair Jobs</span></div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__ico mb-4" style="background:var(--amber-soft);color:#b06a05">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:20px;height:20px"><path d="M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7"/></svg>
                </div>
                <div class="rl-kpi__label">Total Products</div>
                <div class="rl-kpi__val tnum">{{ number_format($totalProducts,0,',','.') }} <span class="fs-6 fw-normal text-muted">Items</span></div>
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
                        <h3 class="fw-bold mb-1" style="font-size:16px">Sales Trends</h3>
                        <p class="text-muted mb-0" style="font-size:12px">Revenue 7 hari terakhir</p>
                    </div>
                </div>
                @php $max = max(1, $trend->max('total')); @endphp
                <div class="d-flex align-items-end gap-3" style="height:220px">
                    @foreach ($trend as $t)
                        <div class="flex-fill d-flex flex-column align-items-center gap-2" style="height:100%">
                            <div class="w-100 mt-auto rounded-top" title="{{ rl_rp($t['total']) }}"
                                 style="height:{{ max(4, (int)($t['total']/$max*180)) }}px;background:linear-gradient(180deg,var(--red),#e98b8e);min-height:4px"></div>
                            <small class="text-muted">{{ $t['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Recent transactions --}}
        <div class="col-lg-4">
            <div class="rl-card h-100">
                <div class="d-flex justify-content-between align-items-center p-3 pb-2">
                    <h3 class="fw-bold mb-0" style="font-size:16px">Recent Transactions</h3>
                    <a href="{{ route('transaksi.index') }}" class="fw-semibold text-decoration-none" style="font-size:12px;color:var(--red-strong)">View All &rarr;</a>
                </div>
                <div class="px-2 pb-2">
                    @forelse ($recent as $t)
                        <div class="d-flex align-items-center justify-content-between px-2 py-2 border-bottom" style="border-color:var(--line-2)!important">
                            <div>
                                <div class="fw-semibold" style="font-size:13px">Nota #{{ $t->kode_nota }}</div>
                                <div class="text-muted" style="font-size:11px">{{ $t->pegawai?->nama_pegawai }} · {{ $t->created_at->format('d M H:i') }}</div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold tnum" style="font-size:13px">{{ rl_rp($t->total) }}</div>
                                <span class="rl-pill green" style="font-size:9px">PAID</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4" style="font-size:13px">Belum ada transaksi.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Critical stock --}}
    <div class="rl-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold mb-0" style="font-size:16px">Critical Stock Levels</h3>
            <span class="fw-semibold" style="font-size:12.5px;color:var(--red-strong)">Inventory Management →</span>
        </div>
        <div class="row g-3">
            @forelse ($criticalStock as $p)
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3 p-2 rounded" style="background:var(--bg)">
                        <div class="rounded" style="width:44px;height:44px;background:#e6e7ea"></div>
                        <div class="flex-fill">
                            <div class="fw-semibold" style="font-size:13px">{{ $p->nama_produk }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $p->sku }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold {{ $p->jumlah_produk == 0 ? 'text-danger' : '' }}" style="font-size:13px;color:var(--red-strong)">{{ $p->jumlah_produk }} Left</div>
                            <div class="text-muted" style="font-size:10px">{{ $p->statusStok() }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0" style="font-size:13px">Semua stok aman.</p>
            @endforelse
        </div>
    </div>
</x-layouts.app>
