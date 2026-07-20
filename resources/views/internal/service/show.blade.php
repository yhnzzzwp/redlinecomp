<x-layouts.app active="service" :title="'Servis '.$service->nomor_resi">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
        $urutan = \App\Enums\StatusService::cases();
        $now = array_search($service->status, $urutan, true);
    @endphp

    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('service') }}" class="text-muted text-decoration-none" style="font-size:13px">&larr; Semua Servis</a>
    </div>
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">{{ $service->nama_barang }}</h1>
            <p class="text-muted mb-0" style="font-size:13px">
                <b class="tnum" style="color:var(--red-strong)">{{ $service->nomor_resi }}</b>
                &middot; {{ $service->nama_customer }} @if ($service->nomor_hp_customer) &middot; {{ $service->nomor_hp_customer }} @endif
            </p>
        </div>
        <span class="rl-pill {{ $service->status->warna() }}" style="font-size:12px">{{ $service->status->value }}</span>
    </div>

    {{-- Stepper --}}
    <div class="rl-card p-4">
        <div class="rl-step-wrap">
            @foreach ($urutan as $i => $st)
                <div class="rl-step {{ $i < $now ? 'done' : ($i === $now ? 'now' : '') }}">
                    <div class="rl-step__dot">{{ $i < $now ? '✓' : ($i === $now ? '●' : '') }}</div>
                    {{ $st->value }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-3">
        {{-- Kiri: info + update status --}}
        <div class="col-lg-7 d-flex flex-column gap-3">
            <div class="rl-card p-4">
                <h3 class="fw-bold mb-3" style="font-size:15px">Detail Perangkat</h3>
                <div class="row g-3" style="font-size:13px">
                    <div class="col-6"><div class="text-muted" style="font-size:11.5px">Masuk</div><b>{{ $service->tanggal_masuk?->format('d M Y') ?? '—' }}</b></div>
                    <div class="col-6"><div class="text-muted" style="font-size:11.5px">Estimasi Selesai</div><b>{{ $service->estimasi_selesai?->format('d M Y') ?? '—' }}</b></div>
                    <div class="col-6"><div class="text-muted" style="font-size:11.5px">Teknisi</div><b>{{ $service->pegawai?->nama_pegawai ?? '—' }}</b></div>
                    <div class="col-6"><div class="text-muted" style="font-size:11.5px">Estimasi Biaya</div><b>{{ $rp($service->biaya_service) }}</b></div>
                    <div class="col-12"><div class="text-muted" style="font-size:11.5px">Masalah</div><div>{{ $service->masalah }}</div></div>
                </div>
            </div>

            <div class="rl-card p-4">
                <h3 class="fw-bold mb-3" style="font-size:15px">Update Status</h3>
                <form method="POST" action="{{ route('service.status', $service) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Status baru</label>
                        <select name="status" class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
                            @foreach ($statusList as $st)
                                <option value="{{ $st->value }}" @selected($service->status === $st)>{{ $st->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Catatan</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan pengerjaan…"
                                  class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px"></textarea>
                    </div>
                    <button type="submit" class="btn-redline">Simpan Status</button>
                </form>
            </div>

            <div class="rl-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0" style="font-size:15px">Sparepart</h3>
                    <span class="text-muted tnum" style="font-size:12.5px">Total {{ $rp($service->parts->sum('subtotal')) }}</span>
                </div>
                @forelse ($service->parts as $part)
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color:var(--line-2)!important;font-size:12.5px">
                        <span>{{ $part->nama_part }}</span>
                        <span class="text-muted">&times;{{ $part->jumlah }}</span>
                        <b class="ms-auto tnum">{{ $rp($part->subtotal) }}</b>
                    </div>
                @empty
                    <p class="text-muted" style="font-size:12.5px">Belum ada sparepart.</p>
                @endforelse
                <form method="POST" action="{{ route('service.part', $service) }}" class="row g-2 mt-2">
                    @csrf
                    <div class="col-5"><input type="text" name="nama_part" placeholder="Nama sparepart" class="w-100" style="border:1px solid var(--line);border-radius:8px;padding:8px 11px;font-size:12.5px" required></div>
                    <div class="col-2"><input type="number" name="jumlah" placeholder="Qty" value="1" min="1" class="w-100" style="border:1px solid var(--line);border-radius:8px;padding:8px 11px;font-size:12.5px" required></div>
                    <div class="col-3"><input type="number" name="harga" placeholder="Harga" min="0" class="w-100" style="border:1px solid var(--line);border-radius:8px;padding:8px 11px;font-size:12.5px" required></div>
                    <div class="col-2"><button type="submit" class="btn-ghost btn-sm w-100">+ Tambah</button></div>
                </form>
            </div>
        </div>

        {{-- Kanan: riwayat status --}}
        <div class="col-lg-5">
            <div class="rl-card p-4">
                <h3 class="fw-bold mb-3" style="font-size:15px">Riwayat Status</h3>
                <div class="rl-timeline">
                    @foreach ($service->riwayat as $r)
                        <div class="rl-timeline__item">
                            <div class="d-flex justify-content-between">
                                <b style="font-size:13px">{{ $r->status->value }}</b>
                                <span class="text-muted tnum" style="font-size:11px">{{ $r->created_at->format('d M H:i') }}</span>
                            </div>
                            @if ($r->catatan)<div class="text-muted" style="font-size:12px">{{ $r->catatan }}</div>@endif
                            <div class="text-muted" style="font-size:11px">oleh {{ $r->pegawai?->nama_pegawai ?? 'Sistem' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
