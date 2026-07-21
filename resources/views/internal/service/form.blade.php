<x-layouts.app active="service" title="Tambah Servis">
    <div class="rl-page-header">
        <a href="{{ route('service') }}" class="rl-back-link text-decoration-none rl-text-sm">&larr; Kembali</a>
        <h1 class="rl-page-title mb-1 mt-2">Tambah Servis Baru</h1>
        <p class="rl-page-desc">Nomor resi akan dibuat otomatis oleh sistem.</p>
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
            <form method="POST" action="{{ route('service.store') }}" class="rl-form-card rl-card p-4">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-form.input name="nama_customer" label="Nama Pelanggan" required />
            </div>
            <div class="col-md-6">
                <x-form.input name="nomor_hp_customer" label="Nomor HP" placeholder="0812xxxxxxxx" />
            </div>
        </div>

        <div class="mb-3">
            <x-form.input name="nama_barang" label="Nama Barang" required />
        </div>

        <div class="mb-3">
            <x-form.textarea name="masalah" label="Deskripsi Masalah" rows="3" required />
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <x-form.input type="number" name="biaya_service" label="Estimasi Biaya (Rp)" value="0" min="0" />
            </div>
            <div class="col-md-4">
                <x-form.input type="date" name="estimasi_selesai" label="Estimasi Selesai" />
            </div>
            <div class="col-md-4">
                <x-form.select name="teknisi_id" label="Teknisi" :options="[]" placeholder="-- Pilih Teknisi --">
                    <option value="">-- Pilih Teknisi --</option>
                    @foreach($teknisi as $t)
                        <option value="{{ $t->id }}" @selected(old('teknisi_id') == $t->id)>{{ $t->nama_pegawai }}</option>
                    @endforeach
                </x-form.select>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">Simpan &amp; Buat Resi</button>
            <a href="{{ route('service') }}" class="btn-ghost">Batal</a>
        </div>
            </form>
        </div>
    </div>
</x-layouts.app>
