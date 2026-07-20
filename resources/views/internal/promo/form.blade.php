<x-layouts.app active="promo" :title="$promo->exists ? 'Edit Promo' : 'Tambah Promo'">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('promo.index') }}" class="text-muted text-decoration-none" style="font-size:13px">&larr; Kembali</a>
    </div>
    <h1 class="fw-bold" style="font-size:23px;letter-spacing:-.4px">{{ $promo->exists ? 'Edit Promo' : 'Tambah Promo Baru' }}</h1>

    @if ($errors->any())
        <div class="rl-card p-3" style="border-left:4px solid var(--red)">
            <ul class="mb-0" style="font-size:12.5px;color:var(--red-strong)">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $promo->exists ? route('promo.update', $promo) : route('promo.store') }}" class="rl-card p-4" style="max-width:720px">
        @csrf
        @if ($promo->exists) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Nama Promo <span style="color:var(--red)">*</span></label>
                <input type="text" name="nama_promo" value="{{ old('nama_promo', $promo->nama_promo) }}" placeholder="Flash Sale Akhir Tahun"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
            <div class="col-md-5">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Kode <span style="color:var(--red)">*</span></label>
                <input type="text" name="kode_promo" value="{{ old('kode_promo', $promo->kode_promo) }}" placeholder="GAMING40" style="text-transform:uppercase"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px;text-transform:uppercase" required>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Tipe <span style="color:var(--red)">*</span></label>
                <select name="tipe_promo" class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
                    @foreach (\App\Enums\TipePromo::cases() as $t)
                        <option value="{{ $t->value }}" @selected(old('tipe_promo', $promo->tipe_promo?->value) === $t->value)>{{ $t->value }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Besaran <span style="color:var(--red)">*</span></label>
                <input type="number" name="besar_promo" value="{{ old('besar_promo', $promo->besar_promo) }}" min="1" placeholder="40 atau 500000"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
                <small class="text-muted" style="font-size:11px">Persen: 1–100. Nominal: rupiah.</small>
            </div>
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Maks. Diskon (Rp)</label>
                <input type="number" name="maksimal_diskon" value="{{ old('maksimal_diskon', $promo->maksimal_diskon) }}" min="0" placeholder="Opsional"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Min. Transaksi (Rp)</label>
                <input type="number" name="minimal_transaksi" value="{{ old('minimal_transaksi', $promo->minimal_transaksi ?? 0) }}" min="0"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Mulai <span style="color:var(--red)">*</span></label>
                <input type="date" name="waktu_mulai" value="{{ old('waktu_mulai', $promo->waktu_mulai?->format('Y-m-d')) }}"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Berakhir <span style="color:var(--red)">*</span></label>
                <input type="date" name="waktu_berakhir" value="{{ old('waktu_berakhir', $promo->waktu_berakhir?->format('Y-m-d')) }}"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="d-flex align-items-center gap-2" style="font-size:13px">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $promo->aktif ?? true))>
                Promo aktif
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">{{ $promo->exists ? 'Simpan Perubahan' : 'Simpan Promo' }}</button>
            <a href="{{ route('promo.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>
</x-layouts.app>
