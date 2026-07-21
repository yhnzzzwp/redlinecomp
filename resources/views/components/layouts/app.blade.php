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
                <div x-data="{ 
                        query: '{{ request('cari') }}', 
                        open: false,
                        get showDropdown() { return this.query.length > 0 && this.open; },
                        submit() {
                            if (!this.query) return;
                            if (this.query.toUpperCase().startsWith('SRV')) { window.location = '{{ route('service') }}?cari=' + this.query; }
                            else if (this.query.toUpperCase().startsWith('INV')) { window.location = '{{ route('transaksi.index') }}?cari=' + this.query; }
                            else { window.location = '{{ route('produk.index') }}?cari=' + this.query; }
                        }
                    }" 
                    @click.outside="open = false" 
                    class="flex-grow-1 position-relative me-4">
                    <form @submit.prevent="submit" class="rl-search m-0 d-flex align-items-center w-100 bg-transparent p-0 border-0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="text-muted ms-3" style="width:16px;height:16px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                        <input x-model="query" @focus="open = true" type="text" placeholder="Cari produk, servis, atau transaksi…" class="rl-input border-0 bg-transparent w-100 shadow-none outline-none px-3 py-2">
                    </form>
                    <div x-show="showDropdown" x-cloak class="position-absolute bg-white border rounded shadow-sm w-100 mt-1 py-2 z-3" style="top:100%">
                        <a :href="'{{ route('produk.index') }}?cari=' + query" class="d-block px-3 py-2 text-decoration-none text-dark">Cari "<b><span x-text="query"></span></b>" di Produk</a>
                        <a :href="'{{ route('service') }}?cari=' + query" class="d-block px-3 py-2 text-decoration-none text-dark">Cari "<b><span x-text="query"></span></b>" di Servis</a>
                        <a :href="'{{ route('transaksi.index') }}?cari=' + query" class="d-block px-3 py-2 text-decoration-none text-dark">Cari "<b><span x-text="query"></span></b>" di Transaksi</a>
                    </div>
                </div>
                <div class="ms-auto text-end lh-sm">
                    <div class="fw-semibold">{{ auth()->user()->nama_pegawai }}</div>
                    <div class="text-muted small">{{ auth()->user()->role->value }} · {{ now()->translatedFormat('l, d M Y') }}</div>
                </div>
                <div class="rl-avatar">{{ \Illuminate\Support\Str::of(auth()->user()->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
            </div>
            <div class="rl-body" role="main">
                <x-ui.toast />
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
