<x-layouts.app active="service" title="Service Management">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Service Management</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">Kelola dan lacak reparasi perangkat pelanggan &mdash; {{ $aktif }} tiket aktif.</p>
        </div>
        <a href="{{ route('service.create') }}" class="btn-redline">+ Tambah Servis</a>
    </div>

    <div class="rl-card">
        <form method="GET" class="d-flex align-items-center gap-2 p-3 flex-wrap">
            <div class="rl-search" style="max-width:300px">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari resi, pelanggan, barang…"
                       class="border-0 bg-transparent w-100" style="outline:none;font-size:13px">
            </div>
            <select name="status" class="btn-ghost btn-sm" style="padding:8px 12px" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach (\App\Enums\StatusService::cases() as $st)
                    <option value="{{ $st->value }}" @selected(request('status') === $st->value)>{{ $st->value }}</option>
                @endforeach
            </select>
            <button class="btn-ghost btn-sm" type="submit">Cari</button>
        </form>

        <table class="rl-table">
            <thead>
                <tr>
                    <th>Resi</th><th>Pelanggan &amp; Perangkat</th><th>Masalah</th>
                    <th>Masuk</th><th>Status</th><th style="text-align:right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($service as $s)
                    <tr>
                        <td><b class="tnum" style="font-size:12.5px">{{ $s->nomor_resi }}</b></td>
                        <td>
                            <div class="fw-semibold" style="font-size:13px">{{ $s->nama_customer }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $s->nama_barang }}</div>
                        </td>
                        <td class="text-muted" style="font-size:12.5px;max-width:220px">{{ \Illuminate\Support\Str::limit($s->masalah, 48) }}</td>
                        <td class="text-muted tnum" style="font-size:12px">{{ $s->tanggal_masuk?->format('d M Y') ?? '—' }}</td>
                        <td><span class="rl-pill {{ $s->status->warna() }}">{{ $s->status->value }}</span></td>
                        <td style="text-align:right">
                            <a href="{{ route('service.show', $s) }}" class="btn-redline btn-sm" style="padding:7px 14px">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada tiket servis.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($service->hasPages())
            <div class="p-3">{{ $service->links() }}</div>
        @endif
    </div>
</x-layouts.app>
