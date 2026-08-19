<x-layouts.app active="service" title="Manajemen Servis">
    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="rl-page-title mb-1">Manajemen Servis</h1>
            <p class="rl-page-desc mb-0">Kelola dan lacak reparasi perangkat pelanggan &mdash; {{ $aktif }} tiket aktif.</p>
        </div>
        <a href="{{ route('service.create') }}" class="btn-redline">+ Tambah Servis</a>
    </div>

    <div class="rl-card">
        <form method="GET" class="d-flex align-items-center gap-2 p-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-grow-1">
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari resi, pelanggan, barang…"
                       class="rl-input rl-input--sm rl-input--search flex-grow-1">
            </div>
            <select name="status" class="rl-select rl-select--sm" onchange="this.form.submit()">
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
                    <th>Resi</th><th>Pelanggan &amp; Perangkat</th><th>Keluhan</th>
                    <th>Masuk</th><th>Status</th><th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($service as $s)
                    <tr>
                        <td><b class="rl-mono rl-text-sm">{{ $s->nomor_resi }}</b></td>
                        <td>
                            <div class="fw-semibold rl-text-sm">{{ $s->perangkat->nama_customer }}</div>
                            <div class="rl-text-muted rl-text-xs">{{ $s->perangkat->merk_model }}</div>
                        </td>
                        <td class="rl-text-muted rl-text-sm">{{ \Illuminate\Support\Str::limit($s->keluhan, 48) }}</td>
                        <td class="rl-text-muted tnum rl-text-sm">{{ $s->tanggal_masuk?->format('d M Y') ?? '—' }}</td>
                        <td><span class="rl-pill {{ $s->status->warna() }}">{{ $s->status->value }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('service.show', $s) }}" class="btn-redline btn-sm px-3">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center rl-text-muted py-5">Belum ada tiket servis.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($service->hasPages())
            <div class="p-3">{{ $service->links() }}</div>
        @endif
    </div>
</x-layouts.app>
