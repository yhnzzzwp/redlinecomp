<x-layouts.app active="pegawai" :title="$pegawai->exists ? 'Edit Pegawai' : 'Tambah Pegawai'">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('pegawai.index') }}" class="text-muted text-decoration-none" style="font-size:13px">&larr; Kembali</a>
    </div>
    <h1 class="fw-bold" style="font-size:23px;letter-spacing:-.4px">{{ $pegawai->exists ? 'Edit Pegawai' : 'Tambah Pegawai Baru' }}</h1>

    @if ($errors->any())
        <div class="rl-card p-3" style="border-left:4px solid var(--red)">
            <ul class="mb-0" style="font-size:12.5px;color:var(--red-strong)">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $pegawai->exists ? route('pegawai.update', $pegawai) : route('pegawai.store') }}" class="rl-card p-4" style="max-width:720px">
        @csrf
        @if ($pegawai->exists) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Nama Lengkap <span style="color:var(--red)">*</span></label>
                <input type="text" name="nama_pegawai" value="{{ old('nama_pegawai', $pegawai->nama_pegawai) }}" placeholder="Nama lengkap pegawai"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
            <div class="col-md-3">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Username <span style="color:var(--red)">*</span></label>
                <input type="text" name="username" value="{{ old('username', $pegawai->username) }}" placeholder="username"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
            <div class="col-md-3">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Role <span style="color:var(--red)">*</span></label>
                <select name="role" class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
                    @foreach (\App\Enums\RolePegawai::cases() as $r)
                        <option value="{{ $r->value }}" @selected(old('role', $pegawai->role?->value) === $r->value)>{{ $r->value }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Email <span style="color:var(--red)">*</span></label>
                <input type="email" name="email" value="{{ old('email', $pegawai->email) }}" placeholder="email@redline.tech"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px" required>
            </div>
            <div class="col-md-6">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">
                    Password
                    @if ($pegawai->exists)
                        <span class="text-muted fw-normal">(kosongkan bila tidak diubah)</span>
                    @else
                        <span style="color:var(--red)">*</span>
                    @endif
                </label>
                <input type="password" name="password" placeholder="{{ $pegawai->exists ? '••••••••' : 'Min. 8 karakter' }}"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px"
                       {{ $pegawai->exists ? '' : 'required' }}>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">No. HP</label>
                <input type="text" name="nomor_hp" value="{{ old('nomor_hp', $pegawai->nomor_hp) }}" placeholder="08xxxxxxxxxx"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Tanggal Masuk</label>
                <input type="date" name="tanggal_masuk" value="{{ old('tanggal_masuk', $pegawai->tanggal_masuk?->format('Y-m-d')) }}"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
            <div class="col-md-4">
                <label class="fw-semibold d-block mb-1" style="font-size:12.5px">Alamat</label>
                <input type="text" name="alamat_pegawai" value="{{ old('alamat_pegawai', $pegawai->alamat_pegawai) }}" placeholder="Alamat pegawai"
                       class="w-100" style="border:1px solid var(--line);border-radius:9px;padding:10px 13px;font-size:13.5px">
            </div>
        </div>

        <div class="mb-4">
            <label class="d-flex align-items-center gap-2" style="font-size:13px">
                <input type="checkbox" name="masih_bekerja" value="1" @checked(old('masih_bekerja', $pegawai->masih_bekerja ?? true))>
                Pegawai masih bekerja (aktif)
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">{{ $pegawai->exists ? 'Simpan Perubahan' : 'Simpan Pegawai' }}</button>
            <a href="{{ route('pegawai.index') }}" class="btn-ghost">Batal</a>
        </div>
    </form>
</x-layouts.app>
