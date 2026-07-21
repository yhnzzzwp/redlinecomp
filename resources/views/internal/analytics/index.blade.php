<x-layouts.app active="analytics" title="Analisis Penjualan">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="rl-page-title mb-1">Analisis Penjualan</h1>
            <p class="rl-page-desc mb-0">
                Laporan penjualan dan statistik periode ini.
                <span class="rl-badge-owner ms-1">Khusus Owner</span>
            </p>
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
                @php $max = max(1, collect($trend)->max('total')); @endphp
                <div class="rl-chart-bar-wrap">
                    @foreach ($trend as $t)
                        <div class="rl-chart-bar">
                            <div class="rl-chart-bar__fill rounded-top" title="{{ $rp($t['total']) }}"
                                 style="height:{{ max(4, (int)($t['total']/$max*100)) }}%;"></div>
                            <small class="rl-chart-bar__label">{{ $t['label'] }}</small>
                        </div>
                    @endforeach
                </div>
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
                    <tr><td colspan="3" class="text-center p-4 rl-text-muted">Belum ada produk terjual periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
