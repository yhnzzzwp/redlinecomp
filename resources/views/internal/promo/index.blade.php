<x-layouts.app active="promo" title="Manajemen Promo">
    @php
        $rp = \App\Support\Uang::rupiah(...);
        $besaran = fn ($p) => $p->tipe_promo === \App\Enums\TipePromo::Persen ? $p->besar_promo.'%' : $rp($p->besar_promo);
    @endphp

    <div class="rl-page-header d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h1 class="rl-page-title mb-1">Manajemen Promo</h1>
            <p class="rl-page-desc mb-0">Kelola kode diskon &amp; kupon promo toko &mdash; Total {{ $aktif }} promo aktif.</p>
        </div>
        <a href="{{ route('promo.create') }}" class="btn-redline">+ Tambah Promo</a>
    </div>

    {{-- Kartu promo --}}
    <div class="row g-3">
        @forelse ($promo as $p)
            <div class="col-md-4">
                <div class="rl-card h-100 overflow-hidden">
                    <div class="rl-promo-header">
                        <span class="rl-promo-header__type">{{ $p->tipe_promo->value }}</span>
                        <div class="rl-promo-header__code tnum">{{ $p->kode_promo }}</div>
                        <div class="rl-promo-header__value">{{ $besaran($p) }}</div>
                        <div class="rl-promo-header__name">{{ $p->nama_promo }}</div>
                    </div>
                    <div class="p-3">
                        <div class="d-flex justify-content-between rl-text-xs"><span class="rl-text-muted">Min. transaksi</span><b>{{ $rp($p->minimal_transaksi) }}</b></div>
                        <div class="d-flex justify-content-between mt-1 rl-text-xs"><span class="rl-text-muted">Maks. diskon</span><b>{{ $p->maksimal_diskon ? $rp($p->maksimal_diskon) : '—' }}</b></div>
                        <div class="d-flex justify-content-between mt-1 rl-text-xs"><span class="rl-text-muted">Periode</span><b class="tnum">{{ $p->waktu_mulai->format('d M') }}–{{ $p->waktu_berakhir->format('d M Y') }}</b></div>
                        <div class="d-flex justify-content-between mt-1 rl-text-xs">
                            <span class="rl-text-muted">Kuota</span>
                            <b class="tnum">{{ $p->kuota ? "{$p->terpakai} / {$p->kuota}" : 'Tanpa batas' }}</b>
                        </div>
                        @php $berlaku = $p->sedangBerlaku(); @endphp
                        <span class="rl-pill {{ $berlaku ? 'green' : 'gray' }} mt-2 rl-text-xs">{{ $berlaku ? 'Aktif' : ($p->aktif ? 'Di luar periode' : 'Nonaktif') }}</span>
                        <div class="d-flex gap-2 mt-3">
                            <a href="{{ route('promo.edit', $p) }}" class="btn-ghost btn-sm flex-fill text-center">Edit</a>
                            <form method="POST" action="{{ route('promo.destroy', $p) }}"
                                  x-data="{ kode: @js($p->kode_promo) }"
                                  @submit.prevent="if (confirm('Hapus promo ' + kode + '?')) $el.submit()">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-ghost btn-sm text-danger">Hapus</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="rl-card p-5 text-center rl-text-muted">Belum ada promo. Klik "Tambah Promo" untuk membuat.</div></div>
        @endforelse
    </div>

    @if ($promo->hasPages())
        <div>{{ $promo->links() }}</div>
    @endif
</x-layouts.app>
