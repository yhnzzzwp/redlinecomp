<x-layouts.app active="analytics" title="Analisis Penjualan">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="rl-page-title mb-1">Analisis Penjualan</h1>
            <p class="rl-page-desc mb-0">Laporan penjualan dan statistik kinerja toko periode ini.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('analytics.cetak', ['dari' => request('dari'), 'sampai' => request('sampai')]) }}" class="btn-ghost" target="_blank">⭳ PDF</a>
            <a href="{{ route('analytics.export', ['dari' => request('dari'), 'sampai' => request('sampai')]) }}" class="btn-redline">⭳ Export CSV</a>
        </div>
    </div>

    <form method="GET" class="rl-card p-3 mb-3 d-flex align-items-end gap-3 flex-wrap">
        <div>
            <label class="rl-label mb-1">Dari Tanggal</label>
            <input type="date" name="dari" value="{{ $dari->format('Y-m-d') }}" class="rl-input rl-input--sm">
        </div>
        <div>
            <label class="rl-label mb-1">Sampai Tanggal</label>
            <input type="date" name="sampai" value="{{ $sampai->format('Y-m-d') }}" class="rl-input rl-input--sm">
        </div>
        <button type="submit" class="btn-ghost btn-sm">Filter</button>
        @if(request()->filled('dari'))
            <a href="{{ route('analytics') }}" class="text-muted rl-text-xs">Reset</a>
        @endif
    </form>

    {{-- KPI --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__label">Pendapatan Hari Ini</div>
                <div class="rl-kpi__val tnum">{{ $rp($pendapatanHariIni) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__label">Pendapatan Periode Ini</div>
                <div class="rl-kpi__val tnum text-primary">{{ $rp($pendapatanPeriode) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rl-card rl-kpi h-100 border-start border-success border-3">
                <div class="rl-kpi__label">Estimasi Profit Periode Ini</div>
                <div class="rl-kpi__val tnum text-success">{{ $rp($profitPeriode) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__label">Transaksi Periode Ini</div>
                <div class="rl-kpi__val tnum">{{ number_format($transaksiPeriode, 0, ',', '.') }} <span class="rl-text-muted rl-text-sm fw-normal">Nota</span></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Trend 7 Hari --}}
        <div class="col-lg-8">
            <div class="rl-card p-4 h-100">
                <h3 class="rl-section-title mb-4">Tren 7 Hari Terakhir</h3>
                @php $max = max(1, collect($trend)->max('total')); $hasData = collect($trend)->sum('total') > 0; @endphp
                @if($hasData)
                <div x-data="{ activeTooltip: null }" class="position-relative mt-4" style="height: 220px; margin-left: 40px;">
                    <!-- Gridlines & Y-Axis Labels -->
                    <div class="position-absolute w-100 h-100 d-flex flex-column justify-content-between pb-4" style="z-index: 1; pointer-events: none;">
                        @foreach([100, 75, 50, 25, 0] as $pct)
                            <div class="w-100 border-top" style="border-top-style: dashed !important; border-top-color: #E9E9EC !important; position: relative;">
                                <span class="position-absolute text-muted text-end pe-2" style="right: 100%; top: -8px; font-size: 10px; width: 40px;">{{ $pct == 0 ? 0 : ($max * $pct / 100 >= 1000000 ? round($max * $pct / 100 / 1000000, 1).'M' : ($max * $pct / 100 >= 1000 ? round($max * $pct / 100 / 1000, 1).'k' : number_format($max * $pct / 100, 0))) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="rl-chart-bar-wrap h-100 position-relative d-flex align-items-end justify-content-between" style="z-index: 2;">
                        @foreach ($trend as $i => $t)
                            <div class="rl-chart-bar position-relative d-flex flex-column justify-content-end h-100" style="flex: 1; padding: 0 4px;">
                                <div x-show="activeTooltip === {{ $i }}" x-transition class="position-absolute bg-dark text-white rounded px-2 py-1 shadow-sm" style="bottom: {{ max(4, (int)($t['total']/$max*100)) }}%; left: 50%; transform: translate(-50%, -8px); font-size: 11px; white-space: nowrap; z-index: 10; margin-bottom: 4px;">
                                    Rp {{ number_format($t['total'], 0, ',', '.') }}
                                </div>
                                <div class="rl-chart-bar__fill rounded-top w-100" style="height:{{ max(4, (int)($t['total']/$max*100)) }}%; transition: opacity 0.2s, background-color 0.2s; cursor: pointer;" :style="activeTooltip !== null && activeTooltip !== {{ $i }} ? 'opacity: 0.6' : 'opacity: 1'" @mouseenter="activeTooltip = {{ $i }}" @mouseleave="activeTooltip = null" @touchstart="activeTooltip = {{ $i }}"></div>
                                <small class="rl-chart-bar__label mt-2 text-center d-block">{{ $t['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-5 d-flex flex-column align-items-center justify-content-center text-muted h-100">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3" style="color: #E9E9EC;">
                        <path d="M3 3v18h18"></path>
                        <path d="M18 17V9"></path>
                        <path d="M13 17V5"></path>
                        <path d="M8 17v-3"></path>
                    </svg>
                    <span class="rl-text-sm">Belum Ada Data Penjualan Periode Ini</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Pendapatan by Kategori --}}
        <div class="col-lg-4">
            <div class="rl-card p-4 h-100">
                <h3 class="rl-section-title mb-4">Berdasarkan Jenis (Periode Ini)</h3>
                <div class="d-flex flex-column gap-3">
                    @php $totalSemua = max(1, $pendapatanKategori->sum('total_pendapatan')); @endphp
                    @foreach ($pendapatanKategori as $kat)
                        @php $pct = round($kat->total_pendapatan / $totalSemua * 100); @endphp
                        <div>
                            <div class="d-flex justify-content-between mb-1 rl-text-sm">
                                <span>{{ $kat->tipe->value ?? $kat->tipe }}</span>
                                <b class="tnum">{{ $rp($kat->total_pendapatan) }}</b>
                            </div>
                            <div class="progress">
                                <div class="progress-bar {{ $kat->tipe->value === 'Produk' ? 'bg-primary' : 'bg-warning' }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                    @if($pendapatanKategori->isEmpty())
                        <div class="rl-text-muted text-center py-4 rl-text-xs">Belum ada data pendapatan.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="rl-card mt-3 overflow-hidden">
        <div class="p-4 border-bottom rl-divider-light">
            <h3 class="rl-section-title mb-0">Produk Terlaris Periode Ini</h3>
        </div>
        <table class="rl-table">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th class="text-center">Terjual</th>
                    <th class="text-end">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($produkTerlaris as $p)
                    <tr>
                        <td class="fw-semibold">{{ $p->nama_item }}</td>
                        <td class="text-center tnum">{{ $p->total_terjual }}</td>
                        <td class="text-end tnum fw-bold">{{ $rp($p->total_pendapatan) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3" style="color: #E9E9EC;">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                                <polyline points="2 12 12 17 22 12"></polyline>
                            </svg>
                            <span class="rl-text-sm">Belum ada produk terjual periode ini.</span>
                        </div>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
