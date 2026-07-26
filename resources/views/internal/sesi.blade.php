<x-layouts.app active="sesi" title="Sesi Aktif">
    <div class="rl-page-header mb-3">
        <h1 class="rl-page-title mb-1">Sesi Aktif</h1>
        <p class="rl-page-desc mb-0">Perangkat yang sedang login dengan akun Anda. Keluarkan perangkat yang tidak Anda kenali atau lupa di-logout.</p>
    </div>

    <div class="rl-card p-0">
        <div class="table-responsive">
            <table class="rl-table w-100 mb-0">
                <thead>
                    <tr>
                        <th>Perangkat</th>
                        <th>Alamat IP</th>
                        <th>Terakhir Aktif</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarSesi as $sesi)
                        <tr>
                            <td>
                                <b>{{ \App\Support\Perangkat::label($sesi->user_agent) }}</b>
                                @if ($sesi->id === $sesiSekarang)
                                    <span class="rl-pill green rl-text-xs ms-2">Sesi ini</span>
                                @endif
                            </td>
                            <td class="tnum">{{ $sesi->ip_address ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::createFromTimestamp($sesi->last_activity)->diffForHumans() }}</td>
                            <td class="text-end">
                                @if ($sesi->id === $sesiSekarang)
                                    <span class="rl-text-muted rl-text-xs">Gunakan Logout</span>
                                @else
                                    <form method="POST" action="{{ route('sesi.keluarkan', $sesi->id) }}" class="d-inline"
                                          x-data @submit.prevent="if (confirm('Keluarkan perangkat ini? Sesinya berakhir seketika.')) $el.submit()">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-ghost rl-btn-sm">Keluarkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center rl-text-muted py-4">Tidak ada sesi aktif tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($daftarSesi->where('id', '!=', $sesiSekarang)->isNotEmpty())
        <div class="rl-card p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <b>Keluarkan semua perangkat lain</b>
                <p class="mb-0 rl-text-muted rl-text-sm">Semua sesi kecuali yang sedang Anda pakai akan diakhiri seketika.</p>
            </div>
            <form method="POST" action="{{ route('sesi.keluarkan-lain') }}"
                  x-data @submit.prevent="if (confirm('Keluarkan SEMUA perangkat lain?')) $el.submit()">
                @csrf
                <button type="submit" class="btn-redline">Keluarkan Semua</button>
            </form>
        </div>
    @endif
</x-layouts.app>
