@props(['active' => 'Home'])
{{-- Interaksi nav publik memakai vanilla JS (lihat resources/js/app.js) —
     zona publik berjalan di bawah CSP ketat tanpa 'unsafe-eval'. --}}
<nav class="rl-pubnav flex-wrap align-items-center" role="navigation">
    <a href="{{ route('landing') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
        <span class="rl-logo fs-5">REDL<i>INE</i></span><span class="rl-stripe"></span>
    </a>

    <button type="button" data-nav-toggle aria-controls="menu-publik" aria-expanded="false"
            class="d-md-none ms-auto btn-ghost border-0 bg-transparent p-2" aria-label="Buka menu navigasi">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    </button>

    {{-- Overlay + drawer: di HP menu meluncur dari kanan; di ≥md jadi baris biasa. --}}
    <div class="rl-pubnav-overlay d-md-none" data-nav-overlay aria-hidden="true"></div>
    <div id="menu-publik" class="rl-pubnav-menu">
        <div class="rl-pubnav-menu__head d-md-none">
            <span class="rl-logo fs-6">REDL<i>INE</i></span><span class="rl-stripe"></span>
            <button type="button" data-nav-close class="btn-ghost border-0 bg-transparent p-2 ms-auto" aria-label="Tutup menu">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>
        <a href="{{ route('landing') }}" @class(['active' => $active === 'Home'])>Beranda</a>
        <a href="{{ route('cek.servis') }}" @class(['text-nowrap', 'active' => $active === 'Service'])>Lacak Servis</a>
        <a href="{{ route('about') }}" @class(['text-nowrap', 'active' => $active === 'About Us'])>Tentang Kami</a>
    </div>
</nav>
