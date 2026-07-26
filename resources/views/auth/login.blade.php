@php
    /** @var \App\Support\Portal $portal */
    $portal = $portal ?? \App\Support\Portal::Staff;
    $isAdmin = $portal === \App\Support\Portal::Admin;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0b0d11">
    {{-- PWA: kasir bisa memasang POS langsung dari halaman login (perangkat baru). --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <title>Login · {{ $portal->label() }} · Redline Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="rl-login">
    <aside class="rl-login__brand">
        <div class="d-flex align-items-center gap-2">
            <span class="rl-logo">REDL<i>INE</i></span><span class="rl-stripe"></span>
        </div>

        <div>
            <div class="rl-kicker mb-3" style="color:var(--pub-muted)">
                {{ $isAdmin ? 'Ruang kendali' : 'Area kerja' }} <b>·</b> {{ config('redline.store_name') }}
            </div>
            @if ($isAdmin)
                <h1 class="rl-login__portal">Admin<br><i>Console.</i></h1>
                <p class="rl-login__meta mt-3 mb-0">Analitik penjualan, manajemen promo, akun pegawai, dan seluruh operasi toko — khusus Owner.</p>
            @else
                <h1 class="rl-login__portal">Portal<br><i>Karyawan.</i></h1>
                <p class="rl-login__meta mt-3 mb-0">Kasir (POS), manajemen produk, dan tiket servis pelanggan — area kerja harian tim Redline.</p>
            @endif
        </div>

        <div class="rl-login__foot">
            <span class="rl-mono">{{ $portal->host() }}</span>
            <span>&middot;</span>
            <span>Akses terbatas &amp; tercatat</span>
        </div>
    </aside>

    <main class="rl-login__panel">
        <div class="rl-login-card" x-data="{ show: false }">
            <div class="rl-ticks"></div>
            <div class="mb-4">
                <h2 class="rl-title-md mb-1">Masuk {{ $isAdmin ? 'sebagai Owner' : 'sebagai Karyawan' }}</h2>
                <p class="text-muted mb-0 rl-text-sm">Gunakan akun {{ $isAdmin ? 'Owner' : 'karyawan' }} Anda untuk melanjutkan.</p>
            </div>

            @if ($errors->any())
                <div class="rl-err">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="rl-icon-15"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="rl-field">
                    <span class="ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="rl-icon-16"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.3 3-5 7-5s7 1.7 7 5"/></svg>
                    </span>
                    <input type="text" name="login" value="{{ old('login') }}" placeholder="Username atau Email"
                           autocomplete="username" class="{{ $errors->has('login') ? 'err' : '' }}" autofocus required>
                </div>
                <div class="rl-field">
                    <span class="ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="rl-icon-16"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                    </span>
                    <input :type="show ? 'text' : 'password'" name="password" placeholder="Password"
                           autocomplete="current-password" class="pe-5 {{ $errors->has('login') ? 'err' : '' }}" required>
                    <button type="button" @click="show = !show" class="ic border-0 bg-transparent rl-pwd-toggle" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="rl-icon-17"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <label class="d-flex align-items-center gap-2 text-muted rl-text-sm">
                        <input type="checkbox" name="remember" value="1"> Ingat perangkat
                    </label>
                    <span class="rl-text-sm text-muted text-end" x-data="{ tip: false }" @click.outside="tip = false">
                        <a href="#" @click.prevent="tip = !tip" class="text-decoration-none fw-semibold rl-text-red">Lupa Password?</a>
                        <div x-show="tip" x-cloak x-transition.opacity class="mt-1 rl-text-xs">
                            {{ $isAdmin ? 'Hubungi pengelola sistem untuk reset password Owner.' : 'Silakan hubungi Owner untuk me-reset password Anda.' }}
                        </div>
                    </span>
                </div>
                <button type="submit" class="btn-redline w-100">
                    Masuk ke {{ $portal->label() }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="rl-icon-16"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                </button>
            </form>

            <div class="text-center mt-4">
                <span class="rl-text-xs text-muted">
                    {{ $isAdmin ? 'Karyawan? Gunakan portal karyawan yang diberikan Owner.' : 'Owner? Gunakan Admin Console melalui alamat khusus admin.' }}
                </span>
            </div>
        </div>
    </main>
</div>
</body>
</html>
