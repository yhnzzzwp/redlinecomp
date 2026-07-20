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
                <div class="rl-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:16px;height:16px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                    <span>Search product serial or name…</span>
                </div>
                <div class="ms-auto text-end lh-sm">
                    <div class="fw-semibold" style="font-size:13px">{{ auth()->user()->nama_pegawai }}</div>
                    <div class="text-muted" style="font-size:11.5px">{{ auth()->user()->role->value }} · {{ now()->translatedFormat('l, d M Y') }}</div>
                </div>
                <div class="rl-avatar">{{ \Illuminate\Support\Str::of(auth()->user()->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
            </div>
            <div class="rl-body">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
