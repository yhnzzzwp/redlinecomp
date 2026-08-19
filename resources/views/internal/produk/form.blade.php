<x-layouts.app active="produk" :title="$produk->exists ? 'Edit Produk' : 'Tambah Produk'">
    <div class="rl-page-header">
        <a href="{{ route('produk.index') }}" class="rl-back-link text-decoration-none rl-text-sm">&larr; Kembali</a>
        <h1 class="rl-page-title mb-1 mt-2">{{ $produk->exists ? 'Edit Produk' : 'Tambah Produk Baru' }}</h1>
    </div>

    @if ($errors->any())
        <div class="rl-alert rl-alert--error mb-3">
            <b class="rl-text-sm">Periksa kembali isian:</b>
            <ul class="mb-0 mt-1 rl-text-sm">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $produk->exists ? route('produk.update', $produk) : route('produk.store') }}"
                  class="rl-form-card rl-card p-4">
        @csrf
        @if ($produk->exists) @method('PUT') @endif

        <div class="mb-3">
            <x-form.input name="nama_produk" value="{{ $produk->nama_produk }}" label="Nama Produk" required />
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-form.input name="sku" value="{{ $produk->sku }}" label="SKU" placeholder="RL-XXX-000" />
            </div>
            <div class="col-md-6">
                <x-form.select name="kategori_id" label="Kategori" required>
                    <option value="">— Tanpa kategori —</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}" @selected(old('kategori_id', $produk->kategori_id) == $k->id)>{{ $k->nama_kategori }}</option>
                    @endforeach
                </x-form.select>
            </div>
        </div>

        </div>

        <div class="mb-3">
            <x-form.textarea name="deskripsi_produk" label="Deskripsi" rows="3">{{ $produk->deskripsi_produk }}</x-form.textarea>
        </div>

        <div class="mb-4">
            <label class="rl-checkbox-label d-flex align-items-center gap-2 rl-text-sm">
                <input type="checkbox" name="show_katalog" value="1" @checked(old('show_katalog', $produk->show_katalog ?? true))>
                Tampilkan di katalog publik
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">{{ $produk->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
            <a href="{{ route('produk.index') }}" class="btn-ghost">Batal</a>
        </div>
            </form>
        </div>
    </div>
</x-layouts.app>
