<x-layouts.app active="keamanan" title="Keamanan Akun">
    <div class="rl-page-header">
        <div>
            <h1 class="rl-page-title mb-1">Keamanan Akun</h1>
            <p class="rl-page-desc mb-0">Verifikasi dua langkah (2FA) untuk akun Owner di Admin Console.</p>
        </div>
    </div>

    <div class="rl-card rl-form-card p-4">
        @if ($kodePemulihanBaru)
            <div class="rl-alert rl-alert--success mb-3 d-block">
                <div class="fw-bold mb-2">Simpan 6 kode pemulihan ini — hanya ditampilkan SEKALI.</div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($kodePemulihanBaru as $k)
                        <code class="rl-mono rl-card px-3 py-2">{{ $k }}</code>
                    @endforeach
                </div>
                <p class="rl-text-xs text-muted mt-2 mb-0">Masing-masing hanya bisa dipakai sekali, saat aplikasi authenticator tidak tersedia.</p>
            </div>
        @endif

        @if ($aktif)
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="rl-pill green">2FA Aktif</span>
                <span class="rl-text-sm text-muted">Login Admin Console memerlukan kode dari aplikasi authenticator.</span>
            </div>
            <form method="POST" action="{{ route('totp.nonaktifkan') }}" class="d-flex align-items-end gap-2 flex-wrap">
                @csrf
                <div>
                    <label class="rl-label" for="password">Konfirmasi password untuk menonaktifkan</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="rl-input {{ $errors->has('password') ? 'rl-input--error' : '' }}">
                    @error('password')<div class="rl-form-errors mt-1">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn-ghost text-danger">Nonaktifkan 2FA</button>
            </form>
        @else
            <h3 class="rl-section-title">Aktifkan 2FA</h3>
            <ol class="rl-text-sm text-muted ps-3 mb-3">
                <li class="mb-1">Pasang aplikasi authenticator (Google Authenticator / Aegis / 1Password).</li>
                <li class="mb-1">Di HP, ketuk tautan di bawah — atau tambahkan akun manual dengan secret berikut.</li>
                <li>Masukkan 6 digit kode yang muncul, lalu tekan <b>Aktifkan</b>.</li>
            </ol>

            <div class="rl-card p-3 mb-3 rl-border-light">
                <div class="rl-text-caption mb-1">Secret (masukkan manual)</div>
                <code class="rl-mono rl-text-md d-block mb-2" style="word-break:break-all">{{ $secret }}</code>
                <a href="{{ $otpauth }}" class="rl-text-sm text-decoration-none rl-text-red fw-semibold">Buka di aplikasi authenticator &rarr;</a>
            </div>

            <form method="POST" action="{{ route('totp.aktifkan') }}" class="d-flex align-items-end gap-2 flex-wrap">
                @csrf
                <div>
                    <label class="rl-label" for="kode">Kode 6 digit</label>
                    <input type="text" id="kode" name="kode" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                           autocomplete="one-time-code" class="rl-input rl-input-mono {{ $errors->has('kode') ? 'rl-input--error' : '' }}" placeholder="000000">
                    @error('kode')<div class="rl-form-errors mt-1">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn-redline">Aktifkan</button>
            </form>
        @endif
    </div>
</x-layouts.app>
