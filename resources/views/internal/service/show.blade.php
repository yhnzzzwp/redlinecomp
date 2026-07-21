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
                    <div class="rl-step__dot">{{ $i < $now ? '✓' : ($i === $now ? '●' : '') }}</div>
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
                <h3 class="rl-section-title mb-3">Detail Perangkat</h3>
                <div class="row g-3 rl-text-sm">
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Masuk</div><b>{{ $service->tanggal_masuk?->format('d M Y') ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Estimasi Selesai</div><b>{{ $service->estimasi_selesai?->format('d M Y') ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Teknisi</div><b>{{ $service->teknisi?->nama_pegawai ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Dibuat Oleh</div><b>{{ $service->pegawai?->nama_pegawai ?? '—' }}</b></div>
                    <div class="col-6"><div class="rl-text-xs text-muted mb-1">Estimasi Biaya</div><b>{{ $rp($service->biaya_service) }}</b></div>
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
                        <label class="rl-label d-block mb-1">Status Baru</label>
                        <select name="status" class="rl-select w-100">
                            @foreach ($allowed as $st)
                                <option value="{{ $st->value }}">{{ $st->value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="rl-label d-block mb-1">Catatan</label>
                        <textarea name="catatan" rows="2" placeholder="Catatan pengerjaan…" class="rl-textarea w-100"></textarea>
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
                        <b class="ms-auto tnum">{{ $rp($part->subtotal) }}</b>
                    </div>
                @empty
                    <p class="rl-text-muted rl-text-sm">Belum ada suku cadang.</p>
                @endforelse
                <form method="POST" action="{{ route('service.part', $service) }}" class="row g-2 mt-2">
                    @csrf
                    <div class="col-5"><input type="text" name="nama_part" placeholder="Nama suku cadang" class="rl-input w-100" required></div>
                    <div class="col-2"><input type="number" name="jumlah" placeholder="Qty" value="1" min="1" class="rl-input w-100" required></div>
                    <div class="col-3"><input type="number" name="harga" placeholder="Harga" min="0" class="rl-input w-100" required></div>
                    <div class="col-2"><button type="submit" class="btn-ghost btn-sm w-100">+ Tambah</button></div>
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
</x-layouts.app>
