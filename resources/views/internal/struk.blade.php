@php($rp = \App\Support\Uang::rupiah(...))
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Struk #{{ $t->kode_nota }}</title>
<style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    /* Struk 80mm — huruf mono, tanpa warna, hemat kertas thermal. */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Courier New', ui-monospace, monospace; font-size: 12px; color: #000; background: #f3f4f6; }
    .kertas { width: 80mm; margin: 16px auto; background: #fff; padding: 6mm 4mm; box-shadow: 0 4px 18px rgba(0,0,0,.12); }
    .tengah { text-align: center; }
    .kanan { text-align: right; }
    h1 { font-size: 15px; letter-spacing: 1px; }
    .kecil { font-size: 10.5px; }
    .garis { border-top: 1px dashed #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 1px 0; }
    .qty { white-space: nowrap; padding-right: 6px; }
    .total-baris td { font-weight: bold; font-size: 13px; }
    .aksi { text-align: center; margin: 14px auto; }
    .aksi button, .aksi a {
        font-family: inherit; font-size: 13px; padding: 8px 16px; margin: 0 4px;
        border: 1px solid #000; background: #fff; cursor: pointer; text-decoration: none; color: #000;
    }
    @media print {
        body { background: #fff; }
        .kertas { width: auto; margin: 0; padding: 0; box-shadow: none; }
        .aksi { display: none; }
    }
</style>
</head>
<body>
<div class="aksi">
    <button type="button" id="tombol-cetak">Cetak Struk</button>
    <a href="{{ route('transaksi.index') }}">&larr; Kembali</a>
</div>

<div class="kertas">
    <div class="tengah">
        <h1>{{ strtoupper((string) config('redline.store_name')) }}</h1>
        <div class="kecil">Jl. Diponegoro No. 52, Salatiga</div>
        <div class="kecil">WA: {{ config('redline.wa_number') }}</div>
    </div>

    <div class="garis"></div>
    <table class="kecil">
        <tr><td>Nota</td><td class="kanan">#{{ $t->kode_nota }}</td></tr>
        <tr><td>Tanggal</td><td class="kanan">{{ $t->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td class="kanan">{{ $t->pegawai?->nama_pegawai }}</td></tr>
        @if ($t->nama_pembeli)
            <tr><td>Pelanggan</td><td class="kanan">{{ $t->nama_pembeli }}</td></tr>
        @endif
    </table>
    <div class="garis"></div>

    <table>
        @foreach ($t->items as $item)
            <tr><td colspan="2">{{ $item->nama_item }}</td></tr>
            <tr>
                <td class="qty kecil">{{ $item->jumlah }} x {{ $rp($item->harga) }}</td>
                <td class="kanan">{{ $rp($item->subtotal) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="garis"></div>
    <table>
        <tr><td>Subtotal</td><td class="kanan">{{ $rp($t->subtotal) }}</td></tr>
        @if ($t->diskon > 0)
            <tr><td>Diskon{{ $t->promo ? ' ('.$t->promo->kode_promo.')' : '' }}</td><td class="kanan">-{{ $rp($t->diskon) }}</td></tr>
        @endif
        <tr class="total-baris"><td>TOTAL</td><td class="kanan">{{ $rp($t->total) }}</td></tr>
        <tr><td>{{ $t->metode_bayar }}</td><td class="kanan">{{ $rp($t->bayar) }}</td></tr>
        <tr><td>Kembali</td><td class="kanan">{{ $rp($t->kembalian) }}</td></tr>
    </table>
    <div class="garis"></div>

    <div class="tengah kecil">
        Terima kasih atas kunjungan Anda!<br>
        Barang yang dibeli tidak dapat ditukar.
    </div>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.getElementById('tombol-cetak').addEventListener('click', function () { window.print(); });
</script>
</body>
</html>
