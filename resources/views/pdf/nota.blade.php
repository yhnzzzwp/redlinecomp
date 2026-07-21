@php($rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.'))
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #1b1e23; font-size: 12px; }
    .wrap { width: 320px; margin: 0 auto; }
    .head { background: #0b1c30; color: #fff; text-align: center; padding: 16px; }
    .head .logo { font-size: 20px; font-weight: bold; font-style: italic; }
    .head .logo i { color: #ff5b5b; font-style: normal; }
    .head p { margin: 4px 0 0; font-size: 9px; color: #9fb0c4; }
    .meta { padding: 12px 16px; border-bottom: 1px dashed #cfcfcf; }
    .row { width: 100%; }
    .row td { padding: 2px 0; font-size: 11px; }
    .row td.r { text-align: right; }
    .items { padding: 10px 16px; }
    .items td { padding: 3px 0; font-size: 11px; vertical-align: top; }
    .items .sub { color: #8a8f98; font-size: 9px; }
    .tot { padding: 10px 16px; border-top: 1px dashed #cfcfcf; }
    .grand td { font-size: 15px; font-weight: bold; color: #af101a; padding-top: 4px; }
    .foot { text-align: center; padding: 14px 16px; font-size: 9px; color: #8a8f98; border-top: 1px dashed #cfcfcf; }
</style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <div class="logo">{{ config('redline.store_name', 'Redline Komputer') }}</div>
        <p>Jl. Diponegoro No. 52, Salatiga &middot; (0298) 321212</p>
    </div>

    <div class="meta">
        <table class="row">
            <tr><td>No. Nota</td><td class="r"><b>#{{ $t->kode_nota }}</b></td></tr>
            <tr><td>Tanggal</td><td class="r">{{ $t->created_at->format('d M Y H:i') }}</td></tr>
            <tr><td>Kasir</td><td class="r">{{ $t->pegawai?->nama_pegawai }}</td></tr>
            <tr><td>Pelanggan</td><td class="r">{{ $t->nama_pembeli ?? 'Umum' }}</td></tr>
            <tr><td>Metode</td><td class="r">{{ $t->metode_bayar }}</td></tr>
        </table>
    </div>

    <div class="items">
        <table class="row">
            @foreach ($t->items as $item)
                <tr>
                    <td>{{ $item->nama_item }}<div class="sub">{{ $item->jumlah }} &times; {{ $rp($item->harga) }}</div></td>
                    <td class="r">{{ $rp($item->subtotal) }}</td>
                </tr>
            @endforeach
        </table>
    </div>

    <div class="tot">
        <table class="row">
            <tr><td>Subtotal</td><td class="r">{{ $rp($t->subtotal) }}</td></tr>
            @if ($t->diskon > 0)
                <tr><td>Diskon{{ $t->promo ? ' ('.$t->promo->kode_promo.')' : '' }}</td><td class="r">&ndash; {{ $rp($t->diskon) }}</td></tr>
            @endif
            <tr class="grand"><td>TOTAL</td><td class="r">{{ $rp($t->total) }}</td></tr>
            <tr><td>Terbayar</td><td class="r">{{ $rp($t->bayar) }}</td></tr>
            <tr><td>Kembali</td><td class="r">{{ $rp($t->kembalian) }}</td></tr>
        </table>
    </div>

    <div class="foot">
        <div style="margin-bottom: 8px">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(url('/cek-nota?nota='.$t->kode_nota)) }}" width="60" height="60" alt="QR Code">
        </div>
        Terima kasih telah berbelanja di Redline Komputer.<br>
        Dokumen digital &middot; cek keaslian dengan scan QR Code di atas.
    </div>
</div>
</body>
</html>
