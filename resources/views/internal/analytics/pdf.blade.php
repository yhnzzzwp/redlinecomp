<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; border-bottom: 2px solid #de1f26; margin-bottom: 20px; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #de1f26; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; color: #333; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row th { background-color: #fcebeb; color: #de1f26; }
        .footer { margin-top: 50px; text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <h1>REDLINE KOMPUTER</h1>
        <p>Laporan Penjualan (Periode: {{ $dari->format('d M Y') }} - {{ $sampai->format('d M Y') }})</p>
    </div>

    <h3>Data Item Terjual Berdasarkan Kategori</h3>
    <table>
        <thead>
            <tr>
                <th>Kategori</th>
                <th class="text-center">Jumlah Terjual</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pendapatanKategori as $kat)
                <tr>
                    <td>{{ $kat->tipe->value ?? $kat->tipe }}</td>
                    <td class="text-center">{{ (int)$kat->jumlah_terjual }} Item</td>
                </tr>
            @empty
                <tr><td colspan="2" class="text-center">Belum ada data</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th>Total Keseluruhan</th>
                <th class="text-center">{{ (int)$totalPendapatan }} Item</th>
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
            </tr>
        </thead>
        <tbody>
            @forelse($produkTerlaris as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ $p->nama_item }}</td>
                    <td class="text-center">{{ $p->total_terjual }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center">Belum ada data</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>Dicetak oleh: {{ auth()->user()->nama_pegawai ?? 'Sistem' }}</p>
        <p>Tanggal: {{ now()->translatedFormat('d F Y, H:i') }}</p>
    </div>

</body>
</html>
