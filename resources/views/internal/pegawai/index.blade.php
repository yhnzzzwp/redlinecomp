<x-layouts.app active="pegawai" title="Akun Pegawai">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Akun Pegawai</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">
                Kelola akun &amp; data karyawan &mdash; {{ $aktif }} pegawai aktif.
                <span class="rl-pill red ms-1" style="font-size:9px">Khusus Owner</span>
            </p>
        </div>
        <a href="{{ route('pegawai.create') }}" class="btn-redline">+ Tambah Pegawai</a>
    </div>

    {{-- Tabel pegawai --}}
    <div class="rl-card overflow-hidden">
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <thead>
                <tr style="background:var(--bg-card);border-bottom:1px solid var(--line)">
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">Nama</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">Username</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">Email</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">Role</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">No. HP</th>
                    <th style="padding:12px 16px;text-align:left;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">Status</th>
                    <th style="padding:12px 16px;text-align:right;font-weight:600;font-size:11.5px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted)">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pegawai as $p)
                    <tr style="border-bottom:1px solid var(--line)">
                        <td style="padding:12px 16px">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rl-avatar" style="width:32px;height:32px;font-size:11px">{{ \Illuminate\Support\Str::of($p->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
                                <div>
                                    <div class="fw-semibold">{{ $p->nama_pegawai }}</div>
                                    @if ($p->tanggal_masuk)
                                        <div class="text-muted" style="font-size:11px">Sejak {{ $p->tanggal_masuk->format('d M Y') }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 16px"><code style="font-size:12px;background:var(--bg);padding:2px 7px;border-radius:4px">{{ $p->username }}</code></td>
                        <td style="padding:12px 16px">{{ $p->email }}</td>
                        <td style="padding:12px 16px">
                            <span class="rl-pill {{ $p->role === \App\Enums\RolePegawai::Owner ? 'red' : 'blue' }}">{{ $p->role->value }}</span>
                        </td>
                        <td style="padding:12px 16px">{{ $p->nomor_hp ?? '—' }}</td>
                        <td style="padding:12px 16px">
                            <span class="rl-pill {{ $p->masih_bekerja ? 'green' : 'gray' }}">{{ $p->masih_bekerja ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td style="padding:12px 16px;text-align:right">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('pegawai.edit', $p) }}" class="btn-ghost btn-sm">Edit</a>
                                @if ($p->id !== auth()->id())
                                    <form method="POST" action="{{ route('pegawai.destroy', $p) }}"
                                          onsubmit="return confirm('Hapus pegawai {{ $p->nama_pegawai }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-ghost btn-sm" style="color:var(--red-strong)">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--muted)">Belum ada data pegawai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($pegawai->hasPages())
        <div>{{ $pegawai->links() }}</div>
    @endif
</x-layouts.app>
