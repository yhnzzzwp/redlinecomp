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
            <h1 class="fw-bold mt-3 mb-1" style="font-size:20px">Login</h1>
            <p class="text-muted mb-0" style="font-size:13px">Selamat Datang, Silakan Login</p>
        </div>

        @if ($errors->any())
            <div class="rl-err">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="rl-field">
                <span class="ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.3 3-5 7-5s7 1.7 7 5"/></svg>
                </span>
                <input type="text" name="login" value="{{ old('login') }}" placeholder="Username or Email"
                       class="{{ $errors->has('login') ? 'err' : '' }}" autofocus required>
            </div>
            <div class="rl-field">
                <span class="ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 018 0v3"/></svg>
                </span>
                <input :type="show ? 'text' : 'password'" name="password" placeholder="Password"
                       class="{{ $errors->has('login') ? 'err' : '' }}" required style="padding-right:42px">
                <button type="button" @click="show = !show" class="ic border-0 bg-transparent" style="left:auto;right:10px">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:17px;height:17px"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-3">
                <label class="d-flex align-items-center gap-2 text-muted" style="font-size:12.5px">
                    <input type="checkbox" name="remember" value="1"> Remember device
                </label>
                <a href="#" class="text-decoration-none fw-semibold" style="font-size:12.5px;color:var(--red-strong)">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-redline w-100 d-flex align-items-center justify-content-center gap-2">
                Sign In
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
            </button>
        </form>

        <p class="text-center text-muted mt-4 mb-0" style="font-size:11px">
            Demo: <b>owner</b> / <b>rijal</b> — password <b>password</b>
        </p>
    </div>
</div>
</body>
</html>
