<x-layouts.app active="produk" :title="$produk->exists ? 'Edit Produk' : 'Tambah Produk'">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('produk.index') }}" class="text-muted text-decoration-none" style="font-size:13px">&larr; Kembali</a>
    </div>
    <h1 class="fw-bold" style="font-size:23px;letter-spacing:-.4px">{{ $produk->exists ? 'Edit Produk' : 'Tambah Produk Baru' }}</h1>

    @if ($errors->any())
        <div class="rl-card p-3" style="border-left:4px solid var(--red)">
            <b style="color:var(--red-strong);font-size:13px">Periksa kembali isian:</b>
            <ul class="mb-0 mt-1" style="font-size:12.5px;color:var(--red-strong)">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $produk->exists ? route('produk.update', $produk) : route('produk.store') }}"
          class="rl-card p-4" style="max-width:720px">
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
                <x-form.select name="kategori_id" label="Kategori">
                    <option value="">— Tanpa kategori —</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}" @selected(old('kategori_id', $produk->kategori_id) == $k->id)>{{ $k->nama_kategori }}</option>
                    @endforeach
                </x-form.select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <x-form.input type="number" name="harga_modal" value="{{ $produk->harga_modal ?? 0 }}" label="Harga Modal / HPP" min="0" />
            </div>
            <div class="col-md-4">
                <x-form.input type="number" name="harga" value="{{ $produk->harga ?? 0 }}" label="Harga Jual (Rp)" min="0" required />
            </div>
            <div class="col-md-4">
                <x-form.input type="number" name="jumlah_produk" value="{{ $produk->jumlah_produk ?? 0 }}" label="Stok" min="0" required />
            </div>
        </div>

        <div class="mb-3">
            <x-form.textarea name="deskripsi_produk" label="Deskripsi" rows="3">{{ $produk->deskripsi_produk }}</x-form.textarea>
        </div>

        <div class="mb-3">
            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Foto Produk <span class="text-muted" style="font-weight:400">(JPG/PNG/WEBP, maks 2MB)</span></label>
            @if ($produk->foto_produk)
                <img src="{{ asset('storage/'.$produk->foto_produk) }}" alt="" style="width:64px;height:64px;border-radius:8px;object-fit:cover" class="mb-2 d-block">
            @endif
            <input type="file" name="foto" accept="image/*"
                   class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:9px 13px;font-size:13px">
        </div>

        <div class="mb-4">
            <label class="d-flex align-items-center gap-2" style="font-size:13px">
                <input type="checkbox" name="show_katalog" value="1" @checked(old('show_katalog', $produk->show_katalog ?? true))>
                Tampilkan di katalog publik
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">{{ $produk->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
            <a href="{{ route('produk.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>
</x-layouts.app>
