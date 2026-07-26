@props(['active' => '', 'title' => null])
@php $portal = \App\Support\Portal::fromRequest(request()); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' · ' : '' }}{{ $portal->label() }} · Redline Komputer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ mobileNav: false }" class="portal-{{ $portal->value }}">
    <div class="rl-drawer-overlay" :class="mobileNav ? 'open' : ''" @click="mobileNav = false"></div>
    <div class="rl-app">
        <div class="rl-side-wrapper" :class="mobileNav ? 'open' : ''">
            <x-ui.sidebar :active="$active" />
        </div>
        <div class="rl-main">
            <div class="rl-mobile-topbar">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-ghost p-1" @click="mobileNav = true" aria-label="Buka Menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                    <span class="rl-logo fs-6 m-0">REDL<i>INE</i></span>
                </div>
                <div class="rl-avatar" style="width:34px;height:34px;font-size:12px;">{{ \Illuminate\Support\Str::of(auth()->user()->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
            </div>
            <div class="rl-topbar">
                <div x-data="topbarSearch(@js($servisAktif), {
                        produk: @js(route('produk.index')),
                        service: @js(route('service')),
                        transaksi: @js(route('transaksi.index')),
                        serviceShowTpl: @js(route('service.show', ['service' => '__ID__']))
                    })"
                    @click.outside="open = false"
                    class="rl-search-wrap flex-grow-1 position-relative me-4">
                    <form @submit.prevent="submit" class="rl-search rl-search--glass m-0 d-flex align-items-center w-100 p-0">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="text-muted ms-3" style="width:16px;height:16px"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
                        <input x-model="query" @focus="open = true" @keydown.escape="open = false" type="text" placeholder="Cari kode servis, produk, atau transaksi…" class="border-0 bg-transparent w-100 shadow-none px-3 py-2" style="outline:none;color:var(--ink);font-size:13.5px;">
                        <button type="button" x-show="query" @click="query = ''; open = false" class="btn border-0 p-0 me-3 text-muted" aria-label="Bersihkan" style="line-height:0;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </form>
                    <div x-show="showDropdown" x-cloak x-transition.opacity.duration.120ms class="rl-search-menu" style="top:100%">
                        <template x-if="matches.length">
                            <div>
                                <div class="rl-search-menu__label">Servis aktif</div>
                                <template x-for="s in matches" :key="s.id">
                                    <a :href="serviceShow(s.id)" class="rl-search-item">
                                        <span class="rl-search-item__code" x-text="s.resi"></span>
                                        <span class="rl-search-item__desc" x-text="s.barang"></span>
                                        <span class="rl-search-item__status" x-text="s.status"></span>
                                    </a>
                                </template>
                                <div class="rl-search-menu__sep"></div>
                            </div>
                        </template>
                        <template x-if="!matches.length && looksLikeServiceCode">
                            <div class="rl-search-menu__empty">Tidak ada servis aktif dengan kode itu</div>
                        </template>
                        <a :href="produkUrl" class="rl-search-item rl-search-item--muted">Cari "<b x-text="query"></b>" di Produk</a>
                        <a :href="serviceUrl" class="rl-search-item rl-search-item--muted">Cari "<b x-text="query"></b>" di Servis</a>
                        <a :href="transaksiUrl" class="rl-search-item rl-search-item--muted">Cari "<b x-text="query"></b>" di Transaksi</a>
                    </div>
                </div>
                <div class="ms-auto text-end lh-sm d-none d-lg-block">
                    <div class="fw-semibold">{{ auth()->user()->nama_pegawai }}</div>
                    <div class="text-muted small">{{ auth()->user()->role->value }} · {{ now()->translatedFormat('l, d M Y') }}</div>
                </div>
                <div class="rl-avatar d-none d-lg-inline-flex">{{ \Illuminate\Support\Str::of(auth()->user()->nama_pegawai)->explode(' ')->map(fn($w)=>$w[0]??'')->take(2)->implode('') }}</div>
            </div>
            <div class="rl-body" role="main">
                <x-ui.toast />
                {{ $slot }}
            </div>
        </div>
    </div>
    <script nonce="{{ Vite::cspNonce() }}">
        function topbarSearch(services, routes) {
            return {
                services: services,
                routes: routes,
                query: @js(request('cari') ?? ''),
                open: false,
                get q() { return this.query.trim().toLowerCase(); },
                get showDropdown() { return this.query.length > 0 && this.open; },
                get looksLikeServiceCode() {
                    return /^(pk|srv)/i.test(this.query.trim());
                },
                get matches() {
                    if (!this.q) return [];
                    return this.services.filter(s =>
                        (s.resi && s.resi.toLowerCase().includes(this.q)) ||
                        (s.barang && s.barang.toLowerCase().includes(this.q))
                    ).slice(0, 6);
                },
                get produkUrl() { return this.routes.produk + '?cari=' + encodeURIComponent(this.query); },
                get serviceUrl() { return this.routes.service + '?cari=' + encodeURIComponent(this.query); },
                get transaksiUrl() { return this.routes.transaksi + '?cari=' + encodeURIComponent(this.query); },
                serviceShow(id) { return this.routes.serviceShowTpl.replace('__ID__', id); },
                submit() {
                    if (!this.query) return;
                    if (this.matches.length >= 1) { window.location = this.serviceShow(this.matches[0].id); return; }
                    const u = this.query.trim().toUpperCase();
                    if (u.startsWith('PK') || u.startsWith('SRV')) { window.location = this.serviceUrl; }
                    else if (u.startsWith('INV')) { window.location = this.transaksiUrl; }
                    else { window.location = this.produkUrl; }
                },
            };
        }
    </script>
</body>
</html>
