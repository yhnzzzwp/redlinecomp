<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <title>Verifikasi 2FA · Admin Console · Redline Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="rl-login" style="grid-template-columns:1fr">
    <main class="rl-login__panel" style="border-radius:0">
        <div class="rl-login-card">
            <div class="rl-ticks"></div>
            <div class="mb-4">
                <h1 class="rl-title-md mb-1">Verifikasi Dua Langkah</h1>
                <p class="text-muted mb-0 rl-text-sm">Masukkan 6 digit dari aplikasi authenticator Anda, atau salah satu kode pemulihan.</p>
            </div>

            @if ($errors->any())
                <div class="rl-err">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="rl-icon-15"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('totp.verifikasi') }}">
                @csrf
                <div class="rl-field">
                    <span class="ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="rl-icon-16"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                    </span>
                    <input type="text" name="kode" inputmode="numeric" autocomplete="one-time-code" maxlength="16"
                           placeholder="Kode 6 digit / kode pemulihan" class="rl-input-mono" autofocus required>
                </div>
                <button type="submit" class="btn-redline w-100">Verifikasi</button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="rl-text-xs text-muted text-decoration-none">&larr; Kembali ke login</a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
