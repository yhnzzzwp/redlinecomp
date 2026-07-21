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
            <b>!</b> {{ session('error') }}
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
                        <input type="text" x-model="cariProduk" placeholder="Cari menu..." class="rl-input rl-input--sm rl-input--search w-auto">
                        <span class="rl-text-muted rl-text-xs" x-text="`Menampilkan ${produkTampil.length} item`"></span>
                    </div>
                </div>
                <div class="row g-3">
                    <template x-for="p in produkTampil" :key="p.id">
                        <div class="col-md-4">
                            <div class="rl-card h-100 p-3 d-flex flex-column">
                                <div class="rl-product-thumb mb-2"></div>
                                <div class="fw-semibold rl-text-sm" x-text="p.nama"></div>
                                <div class="rl-text-muted rl-text-xs" x-text="`Stok: ${p.stok}`"></div>
                                <div class="fw-bold mt-1 rl-text-price" x-text="rp(p.harga)"></div>
                                <div class="mt-2">
                                    <template x-if="p.stok > 0">
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="button" class="btn-ghost btn-sm px-2 py-1" @click="ubah(p, -1)">−</button>
                                            <span class="tnum fw-semibold" x-text="qty(p.id)"></span>
                                            <button type="button" class="btn-ghost btn-sm px-2 py-1" @click="ubah(p, 1)">+</button>
                                            <button type="button" class="btn-redline btn-sm ms-auto px-3 py-1" @click="ubah(p, 1)">🛒</button>
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
                        <div class="text-center rl-text-muted py-4 rl-text-sm">
                            <div class="fs-1">🛒</div>Belum ada item
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
                                <button type="button" class="btn border-0 p-0 rl-text-muted rl-text-xs" @click="hapus(line.id)">hapus</button>
                            </div>
                            <input type="hidden" :name="`items[${line.id}][tipe]`" :value="line.tipe">
                            <input type="hidden" :name="`items[${line.id}][${line.tipe === 'service' ? 'service_id' : 'produk_id'}]`" :value="line.real_id">
                            <input type="hidden" :name="`items[${line.id}][jumlah]`" :value="line.jumlah">
                        </div>
                    </template>
                </div>

                <div class="mt-2">
                    <input type="text" name="kode_promo" x-model="kodePromo" placeholder="Kode promo (opsional)" class="rl-input w-100 mb-2">
                    <div class="d-flex justify-content-between py-1 rl-text-sm">
                        <span class="rl-text-muted">Subtotal</span><span class="tnum" x-text="rp(subtotal)"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top rl-divider mt-1">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold tnum rl-text-total" x-text="rp(subtotal)"></span>
                    </div>

                    <select name="metode_bayar" class="rl-select w-100 mb-2">
                        @foreach (config('redline.metode_bayar') as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="bayar" x-model.number="bayar" placeholder="Jumlah bayar" min="0" class="rl-input w-100 mb-1">
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
                    if (this.cariProduk !== '') {
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
