<x-layouts.app active="pos" title="POS">
    @php
        $produkData = $produk->map(fn ($p) => [
            'id' => $p->id,
            'nama' => $p->nama_produk,
            'harga' => (int) $p->harga,
            'stok' => (int) $p->jumlah_produk,
            'kategori' => $p->kategori?->nama_kategori ?? 'Lainnya',
        ])->values();
    @endphp

    @if (session('error'))
        <div class="rl-card p-3 d-flex align-items-center gap-2" style="border-left:4px solid var(--red);color:var(--red-strong)">
            <b>!</b> {{ session('error') }}
        </div>
    @endif

    <div x-data="pos(@js($produkData), @js($kategori->pluck('nama_kategori')))" class="row g-3">
        <div class="col-lg-8 d-flex flex-column gap-3">
            <div>
                <h2 class="fw-bold mb-3" style="font-size:18px">Categories</h2>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn-ghost btn-sm" :class="kategoriAktif === null ? 'active-cat' : ''"
                            @click="kategoriAktif = null"
                            :style="kategoriAktif === null ? 'background:var(--red);color:#fff;border-color:var(--red)' : ''">All Menu</button>
                    <template x-for="k in kategori" :key="k">
                        <button type="button" class="btn-ghost btn-sm" @click="kategoriAktif = k"
                                :style="kategoriAktif === k ? 'background:var(--red);color:#fff;border-color:var(--red)' : ''"
                                x-text="k"></button>
                    </template>
                </div>
            </div>

            <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="fw-bold mb-0" style="font-size:18px">Select Menu</h2>
                    <span class="text-muted" style="font-size:12px" x-text="`Showing ${produkTampil.length} items`"></span>
                </div>
                <div class="row g-3">
                    <template x-for="p in produkTampil" :key="p.id">
                        <div class="col-md-4">
                            <div class="rl-card h-100 p-3 d-flex flex-column">
                                <div class="rounded mb-2" style="height:90px;background:linear-gradient(135deg,#1b1e23,#3a3e45)"></div>
                                <div class="fw-semibold" style="font-size:13px" x-text="p.nama"></div>
                                <div class="text-muted" style="font-size:11px" x-text="`Stock: ${p.stok}`"></div>
                                <div class="fw-bold mt-1" style="color:var(--red-strong);font-size:13px" x-text="rp(p.harga)"></div>
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
                                        <button type="button" class="btn-ghost btn-sm w-100" disabled style="opacity:.55">Out</button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <form method="POST" action="{{ route('pos.checkout') }}" class="rl-card p-3 d-flex flex-column" style="position:sticky;top:20px">
                @csrf
                <h2 class="fw-bold mb-3" style="font-size:18px">Bill Details</h2>

                <div class="flex-fill" style="min-height:120px">
                    <template x-if="Object.keys(cart).length === 0">
                        <div class="text-center text-muted py-4" style="font-size:13px">
                            <div style="font-size:26px">🛒</div>Belum ada item
                        </div>
                    </template>
                    <template x-for="line in cartLines" :key="line.id">
                        <div class="d-flex justify-content-between align-items-start py-2 border-bottom" style="border-color:var(--line-2)!important">
                            <div>
                                <div class="fw-semibold" style="font-size:12.5px" x-text="line.nama"></div>
                                <div class="text-muted" style="font-size:11px" x-text="`${line.jumlah} × ${rp(line.harga)}`"></div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold tnum" style="font-size:12.5px" x-text="rp(line.harga * line.jumlah)"></div>
                                <button type="button" class="btn border-0 p-0 text-muted" style="font-size:11px" @click="hapus(line.id)">hapus</button>
                            </div>
                            <input type="hidden" :name="`items[${line.id}][produk_id]`" :value="line.id">
                            <input type="hidden" :name="`items[${line.id}][jumlah]`" :value="line.jumlah">
                        </div>
                    </template>
                </div>

                <div class="mt-2">
                    <input type="text" name="kode_promo" x-model="kodePromo" placeholder="Kode promo (opsional)"
                           class="w-100 mb-2" style="border:1px solid var(--line);border-radius:9px;padding:9px 12px;font-size:13px">
                    <div class="d-flex justify-content-between py-1" style="font-size:13px">
                        <span class="text-muted">Subtotal</span><span class="tnum" x-text="rp(subtotal)"></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-top mt-1" style="border-color:var(--line)!important">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold tnum" style="color:var(--red-strong);font-size:18px" x-text="rp(subtotal)"></span>
                    </div>

                    <select name="metode_bayar" class="w-100 mb-2" style="border:1px solid var(--line);border-radius:9px;padding:9px 12px;font-size:13px">
                        @foreach (config('redline.metode_bayar') as $m)
                            <option value="{{ $m }}">{{ $m }}</option>
                        @endforeach
                    </select>
                    <input type="number" name="bayar" x-model.number="bayar" placeholder="Jumlah bayar" min="0"
                           class="w-100 mb-1" style="border:1px solid var(--line);border-radius:9px;padding:9px 12px;font-size:13px">
                    <div class="d-flex justify-content-between py-1" style="font-size:12.5px">
                        <span class="text-muted">Kembalian</span>
                        <span class="tnum" x-text="rp(Math.max(0, bayar - subtotal))"></span>
                    </div>
                    <input type="hidden" name="nama_pembeli" value="Umum">

                    <button type="submit" class="btn-redline w-100 mt-2" :disabled="Object.keys(cart).length === 0"
                            :style="Object.keys(cart).length === 0 ? 'opacity:.5' : ''">Process Transaction</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function pos(produk, kategori) {
            return {
                produk, kategori,
                kategoriAktif: null,
                cart: {},
                kodePromo: '',
                bayar: 0,
                get produkTampil() {
                    return this.kategoriAktif === null
                        ? this.produk
                        : this.produk.filter(p => p.kategori === this.kategoriAktif);
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
                    else { this.cart[p.id] = { id: p.id, nama: p.nama, harga: p.harga, jumlah: next }; }
                },
                hapus(id) { delete this.cart[id]; },
                rp(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
            };
        }
    </script>
</x-layouts.app>
