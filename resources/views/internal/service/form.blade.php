<x-layouts.app active="service" title="Tambah Servis">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('service') }}" class="text-muted text-decoration-none" style="font-size:13px">&larr; Kembali</a>
    </div>
    <h1 class="fw-bold" style="font-size:23px;letter-spacing:-.4px">Tambah Servis Baru</h1>
    <p class="text-muted" style="font-size:13px;margin-top:-8px">Nomor resi akan dibuat otomatis oleh sistem.</p>

    @if ($errors->any())
        <div class="rl-card p-3" style="border-left:4px solid var(--red)">
            <ul class="mb-0" style="font-size:12.5px;color:var(--red-strong)">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('service.store') }}" class="rl-card p-4" style="max-width:720px">
        @csrf
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Nama Pelanggan <span style="color:var(--red)">*</span></label>
                <input type="text" name="nama_customer" value="{{ old('nama_customer') }}" placeholder="Nama pelanggan"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Nomor HP</label>
                <input type="text" name="nomor_hp_customer" value="{{ old('nomor_hp_customer') }}" placeholder="0812xxxxxxxx"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
        </div>

        <div class="mb-3">
            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Nama Barang <span style="color:var(--red)">*</span></label>
            <input type="text" name="nama_barang" value="{{ old('nama_barang') }}" placeholder="Contoh: Laptop ASUS ROG Zephyrus G14"
                   class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
        </div>

        <div class="mb-3">
            <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Deskripsi Masalah <span style="color:var(--red)">*</span></label>
            <textarea name="masalah" rows="3" placeholder="Jelaskan kerusakan atau keluhan pelanggan…"
                      class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>{{ old('masalah') }}</textarea>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Estimasi Biaya (Rp)</label>
                <input type="number" name="biaya_service" value="{{ old('biaya_service', 0) }}" min="0"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Estimasi Selesai</label>
                <input type="date" name="estimasi_selesai" value="{{ old('estimasi_selesai') }}"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">Simpan &amp; Buat Resi</button>
            <a href="{{ route('service') }}" class="btn-ghost">Batal</a>
        </div>
    </form>
</x-layouts.app>
