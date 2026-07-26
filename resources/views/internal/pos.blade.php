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
        
        $promoData = $promo->map(fn ($pr) => [
            'id' => $pr->id,
            'nama' => $pr->nama_promo,
            'kode' => $pr->kode_promo,
            'tipe' => $pr->tipe_promo->value ?? (string) $pr->tipe_promo,
            'besar' => (int) $pr->besar_promo,
            'min' => (int) $pr->minimal_transaksi,
            'maks' => $pr->maksimal_diskon ? (int) $pr->maksimal_diskon : null,
        ])->values();
    @endphp

    @if (session('error'))
        <div class="rl-alert rl-alert--error mb-3 d-flex align-items-center gap-2">
            <b><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Perhatian"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></b> {{ session('error') }}
        </div>
    @endif

    <div x-data="pos(@js($allItemData), @js($kategoriList), @js($promoData))" class="row g-3">
        <div class="col-lg-8 d-flex flex-column gap-3">
            <div>
                <h2 class="rl-section-title mb-3">Kategori</h2>
                <div class="d-flex gap-2 flex-wrap rl-chip-row">
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
                        <div class="position-relative" @click.outside="openProductDropdown = false">
                            <input type="text" x-model="cariProduk" @focus="openProductDropdown = true" @input="openProductDropdown = true" placeholder="Cari menu..." class="rl-input rl-input--sm rl-input--search w-auto pe-4" aria-label="Cari produk" id="cariProduk" autocomplete="off">
                            <button type="button" class="btn border-0 p-0 position-absolute end-0 top-50 translate-middle-y me-2" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center; color: #6c757d;" x-show="cariProduk !== ''" @click="cariProduk = ''; openProductDropdown = false" aria-label="Bersihkan pencarian">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>

                            {{-- Dropdown pencarian produk --}}
                            <div x-show="openProductDropdown && cariProduk.trim() !== '' && produkSearchDropdown.length > 0" x-cloak class="position-absolute bg-white border rounded-3 shadow-lg py-1 z-3 end-0 mt-1" style="top: 100%; min-width: 320px; max-height: 260px; overflow-y: auto;">
                                <div class="px-3 py-1 rl-text-xs text-muted fw-bold border-bottom">Hasil Pencarian Menu:</div>
                                <template x-for="p in produkSearchDropdown" :key="p.id">
                                    <button type="button" class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2 text-start w-100 border-0 bg-transparent" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" @click="if(p.stok > 0) { ubah(p, 1); openProductDropdown = false; }">
                                        <div>
                                            <div class="fw-semibold rl-text-sm" x-text="p.nama"></div>
                                            <span class="rl-text-xs text-muted" x-text="`${p.kategori} · Stok: ${p.stok}`"></span>
                                        </div>
                                        <div class="text-end ms-2">
                                            <span class="tnum text-danger fw-bold rl-text-xs d-block" x-text="rp(p.harga)"></span>
                                            <span class="badge bg-danger text-white rl-text-xs" x-show="p.stok > 0">+ Tambah</span>
                                            <span class="badge bg-secondary text-white rl-text-xs" x-show="p.stok <= 0">Habis</span>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <span class="rl-text-muted rl-text-xs" x-text="`Menampilkan ${produkTampil.length} item`"></span>
                    </div>
                </div>
                <div class="row g-3">
                    <template x-for="p in produkTampil" :key="p.id">
                        <div class="col-md-4">
                            <div class="rl-card p-3 h-100 d-flex flex-direction-column justify-content-between">
                                <div>
                                    <div class="fw-bold rl-text-sm mb-1" x-text="p.nama"></div>
                                    <div class="tnum text-danger fw-bold mb-2" x-text="rp(p.harga)"></div>
                                </div>

                                <div>
                                    <div class="rl-text-xs text-muted mb-2" x-text="`Stok: ${p.stok}`"></div>
                                    <template x-if="qty(p.id) === 0">
                                        <button type="button" class="btn-ghost w-100 py-1" :disabled="p.stok === 0" @click="ubah(p, 1)">+ Tambah</button>
                                    </template>
                                    <template x-if="qty(p.id) > 0">
                                        <div class="d-flex align-items-center justify-content-between border rounded p-1">
                                            <button type="button" class="btn border-0 p-0 text-danger" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center;" aria-label="Kurangi jumlah" @click="ubah(p, -1)">-</button>
                                            <span class="fw-bold tnum rl-text-sm" x-text="qty(p.id)"></span>
                                            <button type="button" class="btn border-0 p-0 text-danger" style="min-width: 32px; min-height: 32px; display: inline-flex; align-items: center; justify-content: center;" aria-label="Tambah jumlah" @click="ubah(p, 1)" :disabled="qty(p.id) >= p.stok">+</button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <form method="POST" action="{{ route('pos.checkout') }}" class="rl-card p-3">
                @csrf
                <h2 class="rl-section-title mb-3">Struk Pembelian</h2>

                <div class="mb-3">
                    <template x-if="cartLines.length === 0">
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
                    <div class="position-relative mb-2" @click.outside="openPromoDropdown = false">
                        <label for="kode_promo" class="visually-hidden">Kode Promo</label>
                        <input type="text" id="kode_promo" name="kode_promo" x-model="kodePromo" @focus="openPromoDropdown = true" @input="openPromoDropdown = true" placeholder="Pilih / ketik kode promo (opsional)" class="rl-input w-100" autocomplete="off">
                        
                        {{-- Dropdown pilihan kode promo --}}
                        <div x-show="openPromoDropdown && filteredPromos.length > 0" x-cloak class="position-absolute bg-white border rounded-3 shadow-lg py-1 z-3 w-100" style="bottom: 100%; left: 0; max-height: 220px; overflow-y: auto; margin-bottom: 6px;">
                            <div class="px-3 py-1 rl-text-xs text-muted fw-bold border-bottom">Pilih Promo Aktif:</div>
                            <template x-for="pr in filteredPromos" :key="pr.id">
                                <button type="button" class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2 text-start w-100 border-0 bg-transparent" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" @click="kodePromo = pr.kode; openPromoDropdown = false;">
                                    <div>
                                        <div class="fw-bold rl-text-sm text-danger" x-text="pr.kode"></div>
                                        <div class="rl-text-xs text-muted" x-text="pr.nama"></div>
                                    </div>
                                    <span class="badge bg-light text-dark border rl-text-xs ms-2" x-text="pr.tipe === 'Persen' ? `${pr.besar}%` : rp(pr.besar)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between py-1 rl-text-sm">
                        <span class="rl-text-muted">Subtotal</span><span class="tnum" x-text="rp(subtotal)"></span>
                    </div>
                    <div class="d-flex justify-content-between py-1 rl-text-sm text-success" x-show="diskonPromo > 0" x-cloak>
                        <span class="fw-semibold">Diskon Promo (<span x-text="appliedPromoKode"></span>)</span>
                        <span class="tnum fw-semibold" x-text="`- ${rp(diskonPromo)}`"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top rl-divider mt-1">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold tnum rl-text-total" x-text="rp(totalAkhir)"></span>
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
                        <span class="tnum" x-text="rp(Math.max(0, bayar - totalAkhir))"></span>
                    </div>
                    <input type="hidden" name="nama_pembeli" value="Umum">

                    <button type="submit" class="btn-redline w-100 mt-2" :disabled="Object.keys(cart).length === 0">Proses Transaksi</button>
                </div>
            </form>
        </div>
    </div>

    <script nonce="{{ Vite::cspNonce() }}">
        function pos(produk, kategori, promos) {
            return {
                produk, kategori, promos,
                kategoriAktif: null,
                cariProduk: '',
                openProductDropdown: false,
                openPromoDropdown: false,
                cart: {},
                kodePromo: '',
                bayar: 0,

                get produkSearchDropdown() {
                    if (!this.cariProduk || this.cariProduk.trim() === '') return [];
                    const q = this.cariProduk.toLowerCase().trim();
                    return this.produk.filter(p => p.nama.toLowerCase().includes(q)).slice(0, 8);
                },

                get filteredPromos() {
                    if (!this.promos) return [];
                    if (!this.kodePromo || this.kodePromo.trim() === '') return this.promos;
                    const q = this.kodePromo.toLowerCase().trim();
                    return this.promos.filter(p => p.kode.toLowerCase().includes(q) || p.nama.toLowerCase().includes(q));
                },

                get matchedPromo() {
                    if (!this.promos || !this.kodePromo || this.kodePromo.trim() === '') return null;
                    const q = this.kodePromo.toLowerCase().trim();
                    return this.promos.find(p => p.kode.toLowerCase() === q);
                },

                get diskonPromo() {
                    const pr = this.matchedPromo;
                    if (!pr) return 0;
                    if (this.subtotal < pr.min) return 0;

                    let d = 0;
                    if (pr.tipe === 'Persen') {
                        d = Math.floor((this.subtotal * pr.besar) / 100);
                    } else {
                        d = pr.besar;
                    }
                    if (pr.maks && pr.maks > 0) {
                        d = Math.min(d, pr.maks);
                    }
                    return Math.min(d, this.subtotal);
                },

                get appliedPromoKode() {
                    return this.matchedPromo ? this.matchedPromo.kode : '';
                },

                get totalAkhir() {
                    return Math.max(0, this.subtotal - this.diskonPromo);
                },

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
                rp(n) {
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(n);
                }
            }
        }
    </script>
</x-layouts.app>
