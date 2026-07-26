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

    <div id="menu-publik" class="w-100 d-none d-md-flex align-items-center flex-column flex-md-row ms-md-4 mt-3 mt-md-0 gap-3 gap-md-4">
        <a href="{{ route('landing') }}" @class(['active' => $active === 'Home'])>Beranda</a>
        <a href="{{ route('cek.servis') }}" @class(['text-nowrap', 'active' => $active === 'Service'])>Lacak Servis</a>
        <a href="{{ route('about') }}" @class(['text-nowrap', 'active' => $active === 'About Us'])>Tentang Kami</a>
    </div>
</nav>
