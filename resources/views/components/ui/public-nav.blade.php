@props(['active' => 'Home'])
<nav x-data="{ open: false }" class="rl-pubnav flex-wrap align-items-center" role="navigation">
    <a href="{{ route('landing') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
        <span class="rl-logo fs-5">REDL<i>INE</i></span><span class="rl-stripe"></span>
    </a>

    <button @click="open = !open" class="d-md-none ms-auto btn-ghost border-0 bg-transparent p-2" aria-label="Buka menu navigasi" :aria-expanded="open">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    </button>

    <div :class="open ? 'd-flex' : 'd-none'" class="w-100 d-md-flex align-items-center flex-column flex-md-row ms-md-4 mt-3 mt-md-0 gap-3 gap-md-4">
        <a href="{{ route('landing') }}" @class(['active' => $active === 'Home'])>Beranda</a>
        <a href="{{ route('catalogue') }}" @class(['active' => $active === 'Catalogue'])>Katalog</a>
        <a href="{{ route('cek.servis') }}" @class(['text-nowrap', 'active' => $active === 'Service'])>Lacak Servis</a>
        <a href="{{ route('cek.nota') }}" @class(['text-nowrap', 'active' => $active === 'Nota'])>Cek Nota</a>
        <a href="{{ route('about') }}" @class(['text-nowrap', 'active' => $active === 'About Us'])>Tentang Kami</a>

        <a href="{{ route('cek.servis') }}" class="ms-md-auto btn-redline rl-btn-sm mt-3 mt-md-0">Servis Sekarang</a>
    </div>
</nav>
