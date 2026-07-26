<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · Redline Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="rl-login-wrap">
    <div class="rl-login-card" x-data="{ show: false }">
        <div class="text-center mb-4">
            <span class="rl-logo fs-3">REDL<i>INE</i></span>
            <h1 class="rl-title-md mt-3 mb-1">Login</h1>
            <p class="text-muted mb-0 rl-text-sm">Selamat Datang, Silakan Login</p>
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
                       class="{{ $errors->has('login') ? 'err' : '' }}" autofocus required>
            </div>
            <div class="rl-field">
                <span class="ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="rl-icon-16"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                </span>
                <input :type="show ? 'text' : 'password'" name="password" placeholder="Password"
                       class="pe-5 {{ $errors->has('login') ? 'err' : '' }}" required>
                <button type="button" @click="show = !show" class="ic border-0 bg-transparent rl-pwd-toggle">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="rl-icon-17"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <label class="d-flex align-items-center gap-2 text-muted rl-text-sm">
                    <input type="checkbox" name="remember" value="1"> Ingat perangkat
                </label>
                <a href="#" @click.prevent="alert('Silakan hubungi Owner / Administrator toko untuk me-reset password Anda.')" class="text-decoration-none fw-semibold rl-text-sm rl-text-red">Lupa Password?</a>
            </div>
            <button type="submit" class="btn-redline w-100 d-flex align-items-center justify-content-center gap-2">
                Masuk
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="rl-icon-16"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
            </button>
        </form>
    </div>
</div>
</body>
</html>
