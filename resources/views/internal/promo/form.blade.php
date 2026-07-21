<x-layouts.app active="promo" :title="$promo->exists ? 'Edit Promo' : 'Tambah Promo'">
    <div class="rl-page-header">
        <a href="{{ route('promo.index') }}" class="rl-back-link text-decoration-none rl-text-sm">&larr; Kembali</a>
        <h1 class="rl-page-title mb-1 mt-2">{{ $promo->exists ? 'Edit Promo' : 'Tambah Promo Baru' }}</h1>
    </div>

    @if ($errors->any())
        <div class="rl-alert rl-alert--error mb-3">
            <ul class="mb-0 rl-text-sm">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" action="{{ $promo->exists ? route('promo.update', $promo) : route('promo.store') }}" class="rl-form-card rl-card p-4">
        @csrf
        @if ($promo->exists) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <x-form.input name="nama_promo" label="Nama Promo" value="{{ old('nama_promo', $promo->nama_promo) }}" placeholder="Flash Sale Akhir Tahun" required />
            </div>
            <div class="col-md-5">
                <x-form.input name="kode_promo" label="Kode" value="{{ old('kode_promo', $promo->kode_promo) }}" placeholder="GAMING40" class="text-uppercase" required />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <x-form.select name="tipe_promo" label="Tipe" required>
                    @foreach (\App\Enums\TipePromo::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('tipe_promo', $promo->tipe_promo?->value) === $t->value)>{{ $t->value }}</option>
                    @endforeach
                </x-form.select>
            </div>
            <div class="col-md-4">
                <x-form.input type="number" name="besar_promo" label="Besaran" value="{{ old('besar_promo', $promo->besar_promo) }}" min="1" placeholder="40 atau 500000" required />
                <small class="rl-text-muted rl-text-xs mt-1 d-block">Persen: 1–100. Nominal: rupiah.</small>
            </div>
            <div class="col-md-4">
                <x-form.input type="number" name="maksimal_diskon" label="Maks. Diskon (Rp)" value="{{ old('maksimal_diskon', $promo->maksimal_diskon) }}" min="0" placeholder="Opsional" />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-form.input type="number" name="minimal_transaksi" label="Min. Transaksi (Rp)" value="{{ old('minimal_transaksi', $promo->minimal_transaksi ?? 0) }}" min="0" />
            </div>
            <div class="col-md-6">
                <x-form.input type="number" name="kuota" label="Kuota Promo" value="{{ old('kuota', $promo->kuota) }}" min="1" placeholder="Kosongkan jika tanpa batas" />
            </div>
        </div>
        
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-form.input type="date" name="waktu_mulai" label="Mulai" value="{{ old('waktu_mulai', $promo->waktu_mulai?->format('Y-m-d')) }}" required />
            </div>
            <div class="col-md-6">
                <x-form.input type="date" name="waktu_berakhir" label="Berakhir" value="{{ old('waktu_berakhir', $promo->waktu_berakhir?->format('Y-m-d')) }}" required />
            </div>
        </div>

        <div class="mb-4">
            <label class="rl-checkbox-label d-flex align-items-center gap-2 rl-text-sm">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $promo->aktif ?? true))>
                Promo aktif
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">{{ $promo->exists ? 'Simpan Perubahan' : 'Simpan Promo' }}</button>
            <a href="{{ route('promo.index') }}" class="btn-ghost">Batal</a>
        </div>
            </form>
        </div>
    </div>
</x-layouts.app>
