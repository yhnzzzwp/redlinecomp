@props(['active' => '', 'title' => null])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}Redline Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="rl-app">
        <x-ui.sidebar :active="$active" />
        <div class="rl-main">
            <div class="rl-topbar">
                <form method="GET" action="{{ route('produk.index') }}" class="rl-search m-0 d-flex align-items-center" style="background:transparent;padding:0">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px;margin-left:14px;color:var(--muted)"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                    <input type="text" name="cari" placeholder="Search product serial or name…" value="{{ request('cari') }}" 
                           class="border-0 bg-transparent w-100" style="padding:10px 14px;font-size:13.5px;outline:none;color:var(--ink)">
                </form>
                <div class="ms-auto text-end lh-sm">
                    <div class="fw-semibold" style="font-size:13px">{{ auth()->user()->nama_pegawai }}</div>
                    <div class="text-muted" style="font-size:11.5px">{{ auth()->user()->role->value }} · {{ now()->translatedFormat('l, d M Y') }}</div>
                </div>
                <div class="rl-avatar">{{ \Illuminate\Support\Str::of(auth()->user()->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
            </div>
            <div class="rl-body">
                @if (session('success'))
                    <div class="rl-card p-3 d-flex align-items-center gap-2" style="border-left:4px solid var(--green);color:#15803d">
                        <b>✓</b> {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="rl-card p-3 d-flex align-items-center gap-2" style="border-left:4px solid var(--red);color:var(--red-strong)">
                        <b>!</b> {{ session('error') }}
                    </div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
