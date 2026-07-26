<x-layouts.app active="pegawai" :title="$pegawai->exists ? 'Edit Pegawai' : 'Tambah Pegawai'">
    <div class="rl-page-header">
        <a href="{{ route('pegawai.index') }}" class="rl-back-link text-decoration-none rl-text-sm">&larr; Kembali</a>
        <h1 class="rl-page-title mb-1 mt-2">{{ $pegawai->exists ? 'Edit Pegawai' : 'Tambah Pegawai Baru' }}</h1>
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
            <form method="POST" action="{{ $pegawai->exists ? route('pegawai.update', $pegawai) : route('pegawai.store') }}" class="rl-form-card rl-card p-4">
        @csrf
        @if ($pegawai->exists) @method('PUT') @endif

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-form.input name="nama_pegawai" label="Nama Lengkap" value="{{ old('nama_pegawai', $pegawai->nama_pegawai) }}" placeholder="Nama lengkap pegawai" required />
            </div>
            <div class="col-md-3">
                <x-form.input name="username" label="Username" value="{{ old('username', $pegawai->username) }}" placeholder="username" required />
            </div>
            <div class="col-md-3">
                <x-form.select name="role" label="Role" required>
                    @foreach (\App\Enums\RolePegawai::cases() as $r)
                        <option value="{{ $r->value }}" @selected(old('role', $pegawai->role?->value) === $r->value)>{{ $r->value }}</option>
                    @endforeach
                </x-form.select>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <x-form.input type="email" name="email" label="Email" value="{{ old('email', $pegawai->email) }}" placeholder="email@redline.tech" required />
            </div>
            <div class="col-md-6">
                <x-form.input 
                    type="password" 
                    name="password" 
                    label="Password{{ $pegawai->exists ? ' (kosongkan bila tidak diubah)' : '' }}" 
                    placeholder="{{ $pegawai->exists ? '••••••••' : 'Min. 8 karakter, huruf + angka' }}"
                    :required="!$pegawai->exists" 
                />
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <x-form.input name="nomor_hp" label="No. HP" value="{{ old('nomor_hp', $pegawai->nomor_hp) }}" placeholder="08xxxxxxxxxx" />
            </div>
            <div class="col-md-4">
                <x-form.input type="date" name="tanggal_masuk" label="Tanggal Masuk" value="{{ old('tanggal_masuk', $pegawai->tanggal_masuk?->format('Y-m-d')) }}" />
            </div>
            <div class="col-md-4">
                <x-form.input name="alamat_pegawai" label="Alamat" value="{{ old('alamat_pegawai', $pegawai->alamat_pegawai) }}" placeholder="Alamat pegawai" />
            </div>
        </div>

        <div class="mb-4">
            <label class="rl-checkbox-label d-flex align-items-center gap-2 rl-text-sm">
                <input type="checkbox" name="masih_bekerja" value="1" @checked(old('masih_bekerja', $pegawai->masih_bekerja ?? true))>
                Pegawai masih bekerja (aktif)
            </label>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn-redline">{{ $pegawai->exists ? 'Simpan Perubahan' : 'Simpan Pegawai' }}</button>
            <a href="{{ route('pegawai.index') }}" class="btn-ghost">Batal</a>
        </div>
            </form>
        </div>
    </div>
</x-layouts.app>
