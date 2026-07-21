<x-layouts.public active="Catalogue" :title="$produk->nama_produk">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-body rl-container-md pt-5">
        <a href="{{ route('catalogue') }}" class="rl-back-link mb-4">&larr; Kembali ke Katalog</a>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="rl-card overflow-hidden rl-catalogue-img-lg">
                    @if ($produk->foto_produk)
                        <img src="{{ Storage::url($produk->foto_produk) }}" alt="{{ $produk->nama_produk }}" class="rl-catalogue-img">
                    @else
                        <div class="rl-icon-xl">📷</div>
                    @endif
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="d-flex flex-column h-100 justify-content-center">
                    <div class="rl-text-sm mb-2">
                        {{ $produk->kategori?->nama_kategori ?? 'Umum' }} &middot; SKU: <span class="tnum">{{ $produk->sku }}</span>
                    </div>
                    
                    <h1 class="rl-page-title">{{ $produk->nama_produk }}</h1>
                    
                    <div class="rl-text-total-lg mb-3">{{ $rp($produk->harga) }}</div>
                    
                    <div class="mb-4">
                        @if ($produk->jumlah_produk > 5)
                            <span class="rl-pill green">Stok Tersedia</span>
                        @elseif ($produk->jumlah_produk > 0)
                            <span class="rl-pill amber">Stok Terbatas</span>
                        @else
                            <span class="rl-pill red">Stok Habis</span>
                        @endif
                    </div>
                    
                    <div class="mb-4 rl-text-desc">{{ $produk->deskripsi_produk ?: 'Belum ada deskripsi untuk produk ini.' }}</div>
                    
                    <div class="mt-auto">
                        @if ($produk->jumlah_produk > 0)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="btn-redline d-inline-flex align-items-center justify-content-center gap-2 w-100 py-3">
                                <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                                Pesan via WhatsApp
                            </a>
                        @else
                            <button class="btn-ghost d-inline-flex align-items-center justify-content-center gap-2 w-100 py-3" style="cursor:not-allowed" disabled>
                                Pesan via WhatsApp (Stok Habis)
                            </button>
                        @endif
                        <p class="rl-text-sm text-center mt-3 mb-0">Transaksi dilakukan di luar sistem. Hubungi admin kami melalui WhatsApp untuk konfirmasi pesanan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
