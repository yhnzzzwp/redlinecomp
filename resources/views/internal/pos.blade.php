<x-layouts.app active="pos" title="POS">
    @php
        $produkData = $produk->map(fn ($p) => [
            'id' => 'p_'.$p->id,
            'real_id' => $p->id,
            'nama' => $p->nama_produk,
            'harga' => (int) $p->harga,
            'stok' => (int) $p->jumlah_produk,
            'kategori' => $p->kategori?->nama_kategori ?? 'Lainnya',
            'tipe' => 'produk'
        ]);
        $serviceData = $services->map(fn ($s) => [
            'id' => 's_'.$s->id,
            'real_id' => $s->id,
            'nama' => 'Servis: ' . $s->nama_barang . ' (' . $s->nomor_resi . ')',
            'harga' => (int) $s->biaya_service,
            'stok' => 1,
            'kategori' => 'Servis',
            'tipe' => 'service'
        ]);
        $allItemData = $produkData->concat($serviceData)->values();
        
        $kategoriList = $kategori->pluck('nama_kategori')->push('Servis');
    @endphp

    @if (session('error'))
        <div class="rl-alert rl-alert--error mb-3 d-flex align-items-center gap-2">
            <b><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Perhatian"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></b> {{ session('error') }}
        </div>
    @endif

    <div x-data="pos(@js($allItemData), @js($kategoriList))" class="row g-3">
        <div class="col-lg-8 d-flex flex-column gap-3">
            <div>
                <h2 class="rl-section-title mb-3">Kategori</h2>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn-sm" :class="kategoriAktif === null ? 'btn-redline' : 'btn-ghost'"
                            @click="kategoriAktif = null">Semua</button>
                    <template x-for="k in kategori" :key="k">
                        <button type="button" class="btn-sm" :class="kategoriAktif === k ? 'btn-redline' : 'btn-ghost'"
                                @click="kategoriAktif = k" x-text="k"></button>
                    </template>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="rl-section-title mb-0">Pilih Produk</h2>
                    <div class="d-flex align-items-center gap-3">
                        <div class="position-relative">
                            <input type="text" x-model="cariProduk" placeholder="Cari menu..." class="rl-input rl-input--sm rl-input--search w-auto pe-4" aria-label="Cari produk" id="cariProduk">
                            <button type="button" class="btn border-0 p-0 position-absolute end-0 top-50 translate-middle-y me-2" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; color: #6c757d;" x-show="cariProduk !== ''" @click="cariProduk = ''" aria-label="Bersihkan pencarian">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                        <span class="rl-text-muted rl-text-xs" x-text="`Menampilkan ${produkTampil.length} item`"></span>
                    </div>
                </div>
                <div class="row g-3">
                    <template x-for="p in produkTampil" :key="p.id">
                        <div class="col-md-4">
                            <div class="rl-card h-100 p-3 d-flex flex-column" style="cursor: pointer; transition: transform 0.1s ease-in-out, box-shadow 0.1s ease-in-out;" onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';" @click="if(p.stok > 0) ubah(p, 1)">
                                <div class="rl-product-thumb mb-2"></div>
                                <div class="fw-semibold rl-text-sm" x-text="p.nama"></div>
                                <div class="rl-text-muted rl-text-xs" x-text="`Stok: ${p.stok}`"></div>
                                <div class="fw-bold mt-1 rl-text-price" x-text="rp(p.harga)"></div>
                                <div class="mt-2">
                                    <template x-if="p.stok > 0">
                                        <div class="d-flex align-items-center gap-2" @click.stop>
                                            <button type="button" class="btn-ghost btn-sm" style="min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700;" aria-label="Kurangi jumlah" @click="ubah(p, -1)">−</button>
                                            <span class="tnum fw-semibold" x-text="qty(p.id)"></span>
                                            <button type="button" class="btn-ghost btn-sm" style="min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700;" aria-label="Tambah jumlah" @click="ubah(p, 1)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
                                            <button type="button" class="btn-redline btn-sm ms-auto" style="min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center;" aria-label="Tambah ke keranjang" @click="ubah(p, 1)"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg></button>
                                        </div>
                                    </template>
                                    <template x-if="p.stok <= 0">
                                        <button type="button" class="btn-ghost btn-sm w-100" disabled>Habis</button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <form method="POST" action="{{ route('pos.checkout') }}" class="rl-card p-3 d-flex flex-column rl-pos-bill">
                @csrf
                <h2 class="rl-section-title mb-3">Detail Tagihan</h2>

                <div class="flex-fill">
                    <template x-if="Object.keys(cart).length === 0">
                        <div class="text-center rl-text-muted py-5 rl-text-sm d-flex flex-column align-items-center justify-content-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mb-3" style="color: #E9E9EC;">
                                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                                <path d="M3 6h18"></path>
                                <path d="M16 10a4 4 0 0 1-8 0"></path>
                            </svg>
                            <span>Keranjang Kosong &mdash; Pilih produk atau servis untuk memulai</span>
                        </div>
                    </template>
                    <template x-for="line in cartLines" :key="line.id">
                        <div class="d-flex justify-content-between align-items-start py-2 border-bottom rl-divider-light">
                            <div>
                                <div class="fw-semibold rl-text-sm" x-text="line.nama"></div>
                                <div class="rl-text-muted rl-text-xs" x-text="`${line.jumlah} × ${rp(line.harga)}`"></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold tnum rl-text-sm" x-text="rp(line.harga * line.jumlah)"></div>
                                <button type="button" class="btn border-0 p-0 rl-text-muted rl-text-xs mt-1" style="min-width: 44px; min-height: 24px; display: inline-flex; align-items: center; justify-content: flex-end;" aria-label="Hapus item dari keranjang" @click="hapus(line.id)"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg> Hapus</button>
                            </div>
                            <input type="hidden" :name="`items[${line.id}][tipe]`" :value="line.tipe">
                            <input type="hidden" :name="`items[${line.id}][${line.tipe === 'service' ? 'service_id' : 'produk_id'}]`" :value="line.real_id">
                            <input type="hidden" :name="`items[${line.id}][jumlah]`" :value="line.jumlah">
                        </div>
                    </template>
                </div>

                <div class="mt-2">
                    <label for="kode_promo" class="visually-hidden">Kode Promo</label>
                    <input type="text" id="kode_promo" name="kode_promo" x-model="kodePromo" placeholder="Kode promo (opsional)" class="rl-input w-100 mb-2">
                    <div class="d-flex justify-content-between py-1 rl-text-sm">
                        <span class="rl-text-muted">Subtotal</span><span class="tnum" x-text="rp(subtotal)"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top rl-divider mt-1">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold tnum rl-text-total" x-text="rp(subtotal)"></span>
                    </div>

                    <label for="metode_bayar" class="visually-hidden">Metode Pembayaran</label>
                    <select id="metode_bayar" name="metode_bayar" class="rl-select w-100 mb-2">
                        @foreach (config('redline.metode_bayar') as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                    <label for="bayar" class="visually-hidden">Jumlah Bayar</label>
                    <input type="number" id="bayar" name="bayar" x-model.number="bayar" placeholder="Jumlah bayar" min="0" class="rl-input w-100 mb-1">
                    <div class="d-flex justify-content-between py-1 rl-text-sm">
                        <span class="rl-text-muted">Kembalian</span>
                        <span class="tnum" x-text="rp(Math.max(0, bayar - subtotal))"></span>
                    </div>
                    <input type="hidden" name="nama_pembeli" value="Umum">

                    <button type="submit" class="btn-redline w-100 mt-2" :disabled="Object.keys(cart).length === 0">Proses Transaksi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function pos(produk, kategori) {
            return {
                produk, kategori,
                kategoriAktif: null,
                cariProduk: '',
                cart: {},
                kodePromo: '',
                bayar: 0,
                get produkTampil() {
                    let p = this.kategoriAktif === null
                        ? this.produk
                        : this.produk.filter(p => p.kategori === this.kategoriAktif);
                    if (this.cariProduk <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Perhatian"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>== '') {
                        p = p.filter(x => x.nama.toLowerCase().includes(this.cariProduk.toLowerCase()));
                    }
                    return p;
                },
                get cartLines() {
                    return Object.values(this.cart);
                },
                get subtotal() {
                    return this.cartLines.reduce((s, l) => s + l.harga * l.jumlah, 0);
                },
                qty(id) { return this.cart[id]?.jumlah ?? 0; },
                ubah(p, delta) {
                    const cur = this.cart[p.id]?.jumlah ?? 0;
                    const next = Math.min(p.stok, Math.max(0, cur + delta));
                    if (next === 0) { delete this.cart[p.id]; }
                    else { this.cart[p.id] = { id: p.id, real_id: p.real_id, tipe: p.tipe, nama: p.nama, harga: p.harga, jumlah: next }; }
                },
                hapus(id) { delete this.cart[id]; },
                rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
            };
        }
    </script>
</x-layouts.app>
