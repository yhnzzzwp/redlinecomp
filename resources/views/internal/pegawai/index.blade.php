<x-layouts.app active="pegawai" title="Akun Pegawai">
    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="rl-page-title mb-1">Akun Pegawai</h1>
            <p class="rl-page-desc mb-0">Kelola akun &amp; data karyawan &mdash; {{ $aktif }} pegawai aktif.</p>
        </div>
        <a href="{{ route('pegawai.create') }}" class="btn-redline">+ Tambah Pegawai</a>
    </div>

    {{-- Tabel pegawai --}}
    <div class="rl-card overflow-hidden">
        <table class="rl-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>No. HP</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pegawai as $p)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rl-avatar rl-avatar--sm">{{ \Illuminate\Support\Str::of($p->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
                                <div>
                                    <div class="fw-semibold">{{ $p->nama_pegawai }}</div>
                                    @if ($p->tanggal_masuk)
                                        <div class="rl-text-muted rl-text-xs">Sejak {{ $p->tanggal_masuk->format('d M Y') }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td><code>{{ $p->username }}</code></td>
                        <td>{{ $p->email }}</td>
                        <td>
                            <span class="rl-pill {{ $p->role === \App\Enums\RolePegawai::Owner ? 'red' : 'blue' }}">{{ $p->role->value }}</span>
                        </td>
                        <td>{{ $p->nomor_hp ?? '—' }}</td>
                        <td>
                            <span class="rl-pill {{ $p->masih_bekerja ? 'green' : 'gray' }}">{{ $p->masih_bekerja ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('pegawai.edit', $p) }}" class="btn-ghost btn-sm">Edit</a>
                                @if ($p->id !== auth()->id())
                                    <form method="POST" action="{{ route('pegawai.destroy', $p) }}"
                                          x-data @submit.prevent='if (confirm("Hapus pegawai " + @js($p->nama_pegawai) + "?")) $el.submit()'>
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-sm text-danger">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center p-4 rl-text-muted">Belum ada data pegawai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pegawai->hasPages())
        <div>{{ $pegawai->links() }}</div>
    @endif

    <style>
        .rl-avatar--sm { width: 32px; height: 32px; font-size: 11px; }
    </style>
</x-layouts.app>
