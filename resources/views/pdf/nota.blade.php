@php($rp = \App\Support\Uang::rupiah(...))
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; }
    body { margin: 0; color: #15181e; font-size: 13px; }
    .wrap { width: 100%; margin: 0 auto; padding: 30px; }
    .header { width: 100%; border-bottom: 3px solid #15181e; padding-bottom: 16px; margin-bottom: 24px; }
    .header td { vertical-align: top; }
    .header-logo { font-size: 28px; font-weight: 800; font-style: italic; color: #15181e; }
    .stripe { display: inline-block; width: 18px; height: 18px; background: #de1f26; margin: 0 6px; position: relative; top: 2px; }
    .subtitle { font-size: 14px; color: #6b7280; font-weight: bold; margin-top: 6px; }
    .contact { font-size: 12px; color: #6b7280; margin-top: 4px; }
    .title-box { text-align: right; }
    .title-text { font-size: 24px; font-weight: bold; color: #15181e; letter-spacing: 1px; }
    .nota-number { font-size: 14px; color: #6b7280; margin-top: 4px; }
    
    .meta-table { width: 100%; margin-bottom: 24px; }
    .meta-table td { font-size: 12px; vertical-align: top; padding: 4px 0; }
    .meta-label { color: #6b7280; width: 90px; display: inline-block; font-weight: bold; }
    
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .items-table th { text-align: left; background: #f3f4f6; padding: 12px; font-size: 11px; color: #6b7280; text-transform: uppercase; border-bottom: 1px solid #e6e8ec; border-top: 1px solid #e6e8ec; }
    .items-table td { padding: 12px; border-bottom: 1px solid #e6e8ec; font-size: 12px; }
    .items-table th.r, .items-table td.r { text-align: right; }
    
    .bottom-section { width: 100%; }
    .bottom-section td { vertical-align: top; }
    
    .totals-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .totals-table td { padding: 6px 12px; text-align: right; font-size: 13px; }
    .totals-table td:first-child { width: 60%; color: #6b7280; font-weight: bold; }
    .grand td { font-size: 18px; font-weight: bold; color: #b01218; border-top: 2px solid #15181e; padding-top: 12px; padding-bottom: 12px; }
    
    .footer-notes { font-size: 11px; color: #8a8f98; line-height: 1.5; margin-top: 40px; }
</style>
</head>
<body>
<div class="wrap">
    <table class="header">
        <tr>
            <td>
                <div class="header-logo">REDLINE<span class="stripe"></span>KOMPUTER</div>
                <div class="subtitle">Spesialis Servis &amp; Hardware Komputer</div>
                <div class="contact">Jl. Diponegoro No. 52, Salatiga &middot; WA: {{ config('redline.wa_number', '08123456789') }}</div>
            </td>
            <td class="title-box">
                <div class="title-text">INVOICE</div>
                <div class="nota-number">#{{ $t->kode_nota }}</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <div><span class="meta-label">Tanggal</span> : {{ $t->created_at->format('d M Y H:i') }}</div>
                <div><span class="meta-label">Kasir</span> : {{ $t->pegawai?->nama_pegawai }}</div>
            </td>
            <td style="width: 50%;">
                <div><span class="meta-label">Pelanggan</span> : {{ $t->nama_pembeli ?? 'Umum' }}</div>
                <div><span class="meta-label">Metode</span> : {{ $t->metode_bayar }}</div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item / Deskripsi</th>
                <th class="r">Harga</th>
                <th class="r">Qty</th>
                <th class="r">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($t->items as $item)
                <tr>
                    <td><strong>{{ $item->nama_item }}</strong></td>
                    <td class="r">{{ $rp($item->harga) }}</td>
                    <td class="r">{{ $item->jumlah }}</td>
                    <td class="r">{{ $rp($item->subtotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bottom-section">
        <tr>
            <td style="width: 55%;">
                <div class="footer-notes">
                    <strong>Catatan:</strong><br>
                    1. Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.<br>
                    2. Garansi berlaku sesuai dengan ketentuan masing-masing produk.<br><br>
                    Terima kasih telah mempercayakan kebutuhan IT Anda<br>kepada Redline Komputer.
                </div>
            </td>
            <td style="width: 45%;">
                <table class="totals-table">
                    <tr><td>Subtotal</td><td>{{ $rp($t->subtotal) }}</td></tr>
                    @if ($t->diskon > 0)
                        <tr><td>Diskon{{ $t->promo ? ' ('.$t->promo->kode_promo.')' : '' }}</td><td>&ndash; {{ $rp($t->diskon) }}</td></tr>
                    @endif
                    <tr class="grand"><td>TOTAL</td><td>{{ $rp($t->total) }}</td></tr>
                    <tr><td>Terbayar</td><td>{{ $rp($t->bayar) }}</td></tr>
                    <tr><td>Kembali</td><td>{{ $rp($t->kembalian) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
