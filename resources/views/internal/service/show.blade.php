<x-layouts.app active="service" :title="'Servis '.$service->nomor_resi">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
        $urutan = \App\Enums\StatusService::cases();
        $now = array_search($service->status, $urutan, true);
    @endphp

    <div class="rl-page-header">
        <a href="{{ route('service') }}" class="rl-back-link text-decoration-none rl-text-sm">&larr; Semua Servis</a>
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mt-2">
            <div>
                <h1 class="rl-page-title mb-1">{{ $service->nama_barang }}</h1>
                <p class="rl-page-desc mb-0">
                    <b class="tnum text-danger">{{ $service->nomor_resi }}</b>
                    &middot; {{ $service->nama_customer }} @if ($service->nomor_hp_customer) &middot; {{ $service->nomor_hp_customer }} @endif
                </p>
            </div>
            <span class="rl-pill {{ $service->status->warna() }} rl-text-xs">{{ $service->status->value }}</span>
        </div>
    </div>

    {{-- Stepper --}}
    <div class="rl-card p-4 mb-3">
        <div class="rl-step-wrap">
            @foreach ($urutan as $i => $st)
                <div class="rl-step {{ $i < $now ? 'done' : ($i === $now ? 'now' : '') }}">
                    <div class="rl-step__dot">{!! $i < $now ? '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-label="Selesai"><polyline points="20 6 9 17 4 12"/></svg>' : ($i === $now ? '●' : '') !!}</div>
                    {{ $st->value }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-3">
        {{-- Kiri: info + update status --}}
        <div class="col-lg-7 d-flex flex-column gap-3">
            @if(session('wa_link'))
                <div class="rl-alert rl-alert--success p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <b class="fs-6">Status Berhasil Diperbarui</b>
                        <p class="mb-0 rl-text-muted rl-text-sm">Kirim notifikasi WhatsApp ke pelanggan.</p>
                    </div>
                    <a href="{{ session('wa_link') }}" target="_blank" class="btn btn-sm btn-success">Kirim Notifikasi WA</a>
                </div>
            @endif

            <div class="rl-card p-4">
                <h3 class="rl-section-title mb-3">Detail Perangkat &amp; Biaya</h3>
                <div class="row g-3 rl-text-sm">
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Masuk</div><b>{{ $service->tanggal_masuk?->format('d M Y') ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Estimasi Selesai</div><b>{{ $service->estimasi_selesai?->format('d M Y') ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Teknisi</div><b>{{ $service->teknisi?->nama_pegawai ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Dibuat Oleh</div><b>{{ $service->pegawai?->nama_pegawai ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Biaya Jasa Servis</div><b>{{ $rp($service->biaya_service) }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Total Suku Cadang</div><b>{{ $rp($service->parts->sum('subtotal')) }}</b></div>
                    <div class="col-12 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold rl-text-sm">Total Biaya yang Harus Dibayar:</span>
                        <b class="fs-5 text-danger tnum">{{ $rp($service->totalBiaya()) }}</b>
                    </div>
                    <div class="col-12"><div class="rl-text-muted rl-text-xs">Masalah</div><div>{{ $service->masalah }}</div></div>
                </div>
            </div>

            @php $allowed = $service->status->allowedTransitions(); @endphp
            @if (count($allowed) > 0)
            <div class="rl-card p-4">
                <h3 class="rl-section-title mb-3">Update Status</h3>
                @if ($errors->has('status'))
                    <div class="rl-form-errors mb-2 rl-text-sm text-danger">{{ $errors->first('status') }}</div>
                @endif
                <form method="POST" action="{{ route('service.status', $service) }}">
                    @csrf
                    <div class="mb-2">
                        <label for="status" class="rl-label d-block mb-1">Status Baru</label>
                        <select id="status" name="status" class="rl-select w-100">
                            @foreach ($allowed as $st)
                                <option value="{{ $st->value }}">{{ $st->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="catatan" class="rl-label d-block mb-1">Catatan</label>
                        <textarea id="catatan" name="catatan" rows="2" placeholder="Catatan pengerjaan…" class="rl-textarea w-100"></textarea>
                    </div>
                    <button type="submit" class="btn-redline">Simpan Status</button>
                </form>
            </div>
            @else
            <div class="rl-card p-4 text-center">
                <span class="rl-pill gray rl-text-sm">Status sudah final — tidak dapat diubah lagi.</span>
            </div>
            @endif

            <div class="rl-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="rl-section-title mb-0">Suku Cadang</h3>
                    <span class="rl-text-muted tnum rl-text-sm">Total {{ $rp($service->parts->sum('subtotal')) }}</span>
                </div>
                @forelse ($service->parts as $part)
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom rl-divider-light rl-text-sm">
                        <span>{{ $part->nama_part }}</span>
                        <span class="rl-text-muted">&times;{{ $part->jumlah }}</span>
                        <b class="ms-auto tnum me-2">{{ $rp($part->subtotal) }}</b>
                        <form method="POST" action="{{ route('service.part.destroy', [$service, $part]) }}" onsubmit="return confirm('Hapus suku cadang ini dan kembalikan stok ke database?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn border-0 p-0 text-danger" title="Hapus suku cadang" style="min-width: 24px; min-height: 24px; display: inline-flex; align-items: center; justify-content: center;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="rl-text-muted rl-text-sm">Belum ada suku cadang.</p>
                @endforelse
                <form method="POST" action="{{ route('service.part', $service) }}" class="row g-2 mt-2" x-data="partSearch(@js($produkList))">
                    @csrf
                    <input type="hidden" name="produk_id" :value="produkId">
                    <div class="col-5 position-relative" @click.outside="open = false">
                        <label for="nama_part" class="visually-hidden">Nama suku cadang</label>
                        <input type="text" id="nama_part" name="nama_part" x-model="query" @input="onInput" @focus="open = true" @keydown.escape="open = false" placeholder="Cari / ketik suku cadang…" class="rl-input w-100" autocomplete="off" required>

                        <div x-show="open && filteredResults.length > 0" x-cloak class="position-absolute bg-white border rounded-3 shadow-lg py-1 z-3" style="bottom: 100%; right: 0; min-width: 320px; max-height: 240px; overflow-y: auto; margin-bottom: 6px;">
                            <div class="px-3 py-1 rl-text-xs text-muted fw-bold border-bottom">Produk dari Database:</div>
                            <template x-for="item in filteredResults" :key="item.id">
                                <button type="button" class="dropdown-item d-flex justify-content-between align-items-center px-3 py-2 text-start w-100 border-0 bg-transparent" style="cursor: pointer;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'" @click="selectItem(item)">
                                    <div>
                                        <div class="fw-semibold rl-text-sm" x-text="item.nama"></div>
                                        <span class="rl-text-xs text-muted" x-text="`${item.kategori} · Stok: ${item.stok}`"></span>
                                    </div>
                                    <span class="tnum text-danger fw-bold rl-text-xs ms-2" x-text="rp(item.harga)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="col-2">
                        <label for="jumlah" class="visually-hidden">Qty</label>
                        <input type="number" id="jumlah" name="jumlah" placeholder="Qty" value="1" min="1" class="rl-input w-100" required>
                    </div>
                    <div class="col-3">
                        <label for="harga" class="visually-hidden">Harga</label>
                        <input type="number" id="harga" name="harga" x-model.number="harga" placeholder="Harga" min="0" class="rl-input w-100" required>
                    </div>
                    <div class="col-2"><button type="submit" class="btn-ghost btn-sm w-100" aria-label="Tambah suku cadang">+ Tambah</button></div>
                </form>
            </div>
        </div>

        {{-- Kanan: riwayat status --}}
        <div class="col-lg-5">
            <div class="rl-card p-4">
                <h3 class="rl-section-title mb-3">Riwayat Status</h3>
                <div class="rl-timeline">
                    @foreach ($service->riwayat as $r)
                        <div class="rl-timeline__item">
                            <div class="d-flex justify-content-between">
                                <b class="rl-text-sm">{{ $r->status->value }}</b>
                                <span class="rl-text-muted tnum rl-text-xs">{{ $r->created_at->format('d M H:i') }}</span>
                            </div>
                            @if ($r->catatan)<div class="rl-text-muted rl-text-xs">{{ $r->catatan }}</div>@endif
                            <div class="rl-text-muted rl-text-xs">oleh {{ $r->pegawai?->nama_pegawai ?? 'Sistem' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ Vite::cspNonce() }}">
        function partSearch(dbProducts) {
            return {
                query: '',
                harga: '',
                produkId: null,
                open: false,
                items: dbProducts,

                get filteredResults() {
                    if (!this.query || this.query.trim() === '') return [];
                    const q = this.query.toLowerCase().trim();
                    return this.items.filter(item => item.nama.toLowerCase().includes(q)).slice(0, 8);
                },

                selectItem(item) {
                    this.query = item.nama;
                    this.harga = item.harga;
                    this.produkId = item.id;
                    this.open = false;
                },

                onInput() {
                    this.open = true;
                    this.produkId = null;
                },

                rp(n) {
                    return 'Rp ' + Number(n).toLocaleString('id-ID');
                }
            };
        }
    </script>
</x-layouts.app>
