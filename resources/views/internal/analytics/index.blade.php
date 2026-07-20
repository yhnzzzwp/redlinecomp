<x-layouts.app active="analytics" title="Sales Analytics">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Sales Analytics</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">
                Laporan penjualan dan statistik bulan ini.
                <span class="rl-pill red ms-1" style="font-size:9px">Khusus Owner</span>
            </p>
        </div>
        <a href="{{ route('analytics.cetak') }}" class="btn-redline" target="_blank">⭳ Cetak Laporan (PDF)</a>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__label">Pendapatan Hari Ini</div>
                <div class="rl-kpi__val tnum">{{ $rp($pendapatanHariIni) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__label">Pendapatan Bulan Ini</div>
                <div class="rl-kpi__val tnum text-primary">{{ $rp($pendapatanBulanIni) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rl-card rl-kpi h-100">
                <div class="rl-kpi__label">Transaksi Bulan Ini</div>
                <div class="rl-kpi__val tnum">{{ number_format($transaksiBulanIni, 0, ',', '.') }} <span style="font-size:12px;font-weight:400" class="text-muted">Nota</span></div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Trend 7 Hari --}}
        <div class="col-lg-8">
            <div class="rl-card p-4 h-100">
                <h3 class="fw-bold mb-4" style="font-size:16px">Tren 7 Hari Terakhir</h3>
                @php $max = max(1, collect($trend)->max('total')); @endphp
                <div class="d-flex align-items-end gap-3" style="height:250px">
                    @foreach ($trend as $t)
                        <div class="flex-fill d-flex flex-column align-items-center gap-2" style="height:100%">
                            <div class="w-100 mt-auto rounded-top" title="{{ $rp($t['total']) }}"
                                 style="height:{{ max(4, (int)($t['total']/$max*200)) }}px;background:linear-gradient(180deg,var(--red),#e98b8e);min-height:4px;transition:height 0.3s"></div>
                            <small class="text-muted" style="font-size:11px">{{ $t['label'] }}</small>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Pendapatan by Kategori --}}
        <div class="col-lg-4">
            <div class="rl-card p-4 h-100">
                <h3 class="fw-bold mb-4" style="font-size:16px">Berdasarkan Jenis (Bulan Ini)</h3>
                <div class="d-flex flex-column gap-3">
                    @php $totalSemua = max(1, $pendapatanKategori->sum('total_pendapatan')); @endphp
                    @foreach ($pendapatanKategori as $kat)
                        @php $pct = round($kat->total_pendapatan / $totalSemua * 100); @endphp
                        <div>
                            <div class="d-flex justify-content-between mb-1" style="font-size:12.5px">
                                <span>{{ $kat->tipe->value ?? $kat->tipe }}</span>
                                <b class="tnum">{{ $rp($kat->total_pendapatan) }}</b>
                            </div>
                            <div style="background:var(--line);height:8px;border-radius:4px;overflow:hidden">
                                <div style="background:var(--{{ $kat->tipe->value === 'Produk' ? 'blue' : 'amber' }});width:{{ $pct }}%;height:100%"></div>
                            </div>
                        </div>
                    @endforeach
                    @if($pendapatanKategori->isEmpty())
                        <div class="text-muted text-center py-4" style="font-size:12px">Belum ada data pendapatan.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="rl-card mt-3 overflow-hidden">
        <div class="p-4 border-bottom" style="border-color:var(--line-2)!important">
            <h3 class="fw-bold mb-0" style="font-size:16px">Produk Terlaris Bulan Ini</h3>
        </div>
        <table class="rl-table">
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th style="text-align:center">Terjual</th>
                    <th style="text-align:right">Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($produkTerlaris as $p)
                    <tr>
                        <td class="fw-semibold">{{ $p->nama_item }}</td>
                        <td style="text-align:center" class="tnum">{{ $p->total_terjual }}</td>
                        <td style="text-align:right" class="tnum fw-bold">{{ $rp($p->total_pendapatan) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center p-4 text-muted">Belum ada produk terjual bulan ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
