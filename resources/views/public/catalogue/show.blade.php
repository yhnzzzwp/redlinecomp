<x-layouts.public active="Catalogue" :title="$produk->nama_produk">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-body mx-auto" style="max-width:1000px;padding-top:40px">
        <a href="{{ route('catalogue') }}" class="text-muted text-decoration-none d-inline-block mb-4" style="font-size:13px">&larr; Kembali ke Katalog</a>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="rl-card overflow-hidden d-flex align-items-center justify-content-center" style="height:400px;background:var(--bg)">
                    @if ($produk->foto_produk)
                        <img src="{{ Storage::url($produk->foto_produk) }}" alt="{{ $produk->nama_produk }}" style="max-height:100%;max-width:100%;object-fit:contain">
                    @else
                        <div class="text-muted" style="font-size:64px">📷</div>
                    @endif
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex flex-column h-100 justify-content-center">
                    <div class="text-muted mb-2" style="font-size:13px">
                        {{ $produk->kategori->nama_kategori }} &middot; SKU: <span class="tnum">{{ $produk->sku }}</span>
                    </div>
                    
                    <h1 class="fw-bold mb-3" style="font-size:28px;letter-spacing:-.5px">{{ $produk->nama_produk }}</h1>
                    
                    <div class="fw-bold tnum mb-3" style="font-size:24px;color:var(--red-strong)">{{ $rp($produk->harga) }}</div>
                    
                    <div class="mb-4">
                        @if ($produk->jumlah_produk > 5)
                            <span class="rl-pill green">Stok Tersedia ({{ $produk->jumlah_produk }})</span>
                        @elseif ($produk->jumlah_produk > 0)
                            <span class="rl-pill amber">Sisa Stok: {{ $produk->jumlah_produk }}</span>
                        @else
                            <span class="rl-pill red">Stok Habis</span>
                        @endif
                    </div>
                    
                    <div class="mb-4 text-muted" style="font-size:14px;line-height:1.6;white-space:pre-wrap">{{ $produk->deskripsi_produk ?: 'Belum ada deskripsi untuk produk ini.' }}</div>
                    
                    <div class="mt-auto">
                        @if ($produk->jumlah_produk > 0)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="btn-redline d-inline-flex align-items-center justify-content-center gap-2 w-100" style="padding:14px">
                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                Order via WhatsApp
                            </a>
                        @else
                            <button class="btn-ghost d-inline-flex align-items-center justify-content-center gap-2 w-100" style="padding:14px;cursor:not-allowed" disabled>
                                Order via WhatsApp (Stok Habis)
                            </button>
                        @endif
                        <p class="text-muted text-center mt-3 mb-0" style="font-size:12px">Transaksi dilakukan di luar sistem. Hubungi admin kami melalui WhatsApp untuk konfirmasi pesanan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
