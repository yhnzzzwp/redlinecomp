<x-layouts.app active="promo" title="Manajemen Promo">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
        $besaran = fn ($p) => $p->tipe_promo === \App\Enums\TipePromo::Persen ? $p->besar_promo.'%' : $rp($p->besar_promo);
    @endphp

    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
        <div>
            <h1 class="fw-bold mb-1" style="font-size:23px;letter-spacing:-.4px">Manajemen Promo</h1>
            <p class="text-muted mb-0" style="font-size:13.5px">
                Kelola kode promo &amp; diskon &mdash; {{ $aktif }} promo aktif.
                <span class="rl-pill red ms-1" style="font-size:9px">Khusus Owner</span>
            </p>
        </div>
        <a href="{{ route('promo.create') }}" class="btn-redline">+ Tambah Promo</a>
    </div>

    {{-- Kartu promo --}}
    <div class="row g-3">
        @forelse ($promo as $p)
            <div class="col-md-4">
                <div class="rl-card h-100 overflow-hidden">
                    <div style="background:linear-gradient(120deg,#c1272c,#7c0c12);color:#fff;padding:16px 18px;position:relative">
                        <span style="position:absolute;top:14px;right:14px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.25);padding:3px 9px;border-radius:999px;font-size:10.5px;font-weight:700">{{ $p->tipe_promo->value }}</span>
                        <div class="tnum" style="color:#ffd7d5;font-size:12px">{{ $p->kode_promo }}</div>
                        <div class="fw-bold" style="font-size:28px">{{ $besaran($p) }}</div>
                        <div style="font-size:12px;color:#ffd7d5">{{ $p->nama_promo }}</div>
                    </div>
                    <div class="p-3">
                        <div class="d-flex justify-content-between" style="font-size:12px"><span class="text-muted">Min. transaksi</span><b>{{ $rp($p->minimal_transaksi) }}</b></div>
                        <div class="d-flex justify-content-between mt-1" style="font-size:12px"><span class="text-muted">Maks. diskon</span><b>{{ $p->maksimal_diskon ? $rp($p->maksimal_diskon) : '—' }}</b></div>
                        <div class="d-flex justify-content-between mt-1" style="font-size:12px"><span class="text-muted">Periode</span><b class="tnum">{{ $p->waktu_mulai->format('d M') }}–{{ $p->waktu_berakhir->format('d M Y') }}</b></div>
                        @php $berlaku = $p->sedangBerlaku(); @endphp
                        <span class="rl-pill {{ $berlaku ? 'green' : 'gray' }} mt-2">{{ $berlaku ? 'Aktif' : ($p->aktif ? 'Di luar periode' : 'Nonaktif') }}</span>
                        <div class="d-flex gap-2 mt-3">
                            <a href="{{ route('promo.edit', $p) }}" class="btn-ghost btn-sm" style="flex:1;text-align:center">Edit</a>
                            <form method="POST" action="{{ route('promo.destroy', $p) }}"
                                  onsubmit="return confirm('Hapus promo {{ $p->kode_promo }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm" style="color:var(--red-strong)">Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="rl-card p-5 text-center text-muted">Belum ada promo. Klik "Tambah Promo" untuk membuat.</div></div>
        @endforelse
    </div>

    @if ($promo->hasPages())
        <div>{{ $promo->links() }}</div>
    @endif
</x-layouts.app>
