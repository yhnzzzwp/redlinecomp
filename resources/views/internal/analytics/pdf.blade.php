<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #c1272c; margin-bottom: 20px; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #c1272c; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f5f7; color: #333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row th { background-color: #ffeaea; color: #c1272c; }
    </style>
</head>
<body>

    <div class="header">
        <h1>REDLINE KOMPUTER</h1>
        <p>Laporan Penjualan Bulan Ini ({{ now()->translatedFormat('F Y') }})</p>
    </div>

    <h3>Pendapatan Berdasarkan Kategori</h3>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="text-right">Total Pendapatan</th>
                <th class="text-right">Total Profit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pendapatanKategori as $kat)
                <tr>
                    <td>{{ $kat->tipe->value ?? $kat->tipe }}</td>
                    <td class="text-right">Rp {{ number_format((int)$kat->total_pendapatan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format((int)$kat->total_profit, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th>Total Keseluruhan</th>
                <th class="text-right">Rp {{ number_format((int)$totalPendapatan, 0, ',', '.') }}</th>
                <th class="text-right">Rp {{ number_format((int)$totalProfit, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <h3>10 Produk Terlaris</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Produk</th>
                <th class="text-center">Terjual</th>
                <th class="text-right">Pendapatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produkTerlaris as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p->nama_item }}</td>
                    <td class="text-center">{{ $p->total_terjual }}</td>
                    <td class="text-right">Rp {{ number_format((int)$p->total_pendapatan, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Dicetak oleh: {{ auth()->user()->nama_pegawai ?? 'Sistem' }}</p>
        <p>Tanggal: {{ now()->translatedFormat('d F Y, H:i') }}</p>
    </div>

</body>
</html>
