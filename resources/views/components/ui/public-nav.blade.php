@props(['active' => 'Home'])
<nav x-data="{ open: false }" class="rl-pubnav flex-wrap align-items-center" role="navigation">
    <span class="rl-logo fs-5">REDL<i>INE</i></span>
    
    <button @click="open = !open" class="d-md-none ms-auto btn-ghost border-0 bg-transparent p-2" aria-label="Toggle menu">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" class="text-ink"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    </button>

    <div :class="open ? 'd-flex' : 'd-none'" class="w-100 d-md-flex align-items-center flex-column flex-md-row ms-md-4 mt-3 mt-md-0 gap-3 gap-md-4">
        <a href="{{ route('landing') }}" @class(['active' => $active === 'Home'])>Beranda</a>
        <a href="{{ route('about') }}" @class(['text-nowrap', 'active' => $active === 'About Us'])>Tentang Kami</a>
        <a href="{{ route('catalogue') }}" @class(['active' => $active === 'Catalogue'])>Katalog</a>
        <a href="{{ route('cek.servis') }}" @class(['active' => $active === 'Service'])>Servis</a>
        
        <a href="{{ route('login') }}" class="ms-md-auto btn-ghost px-3 py-2 text-center mt-3 mt-md-0 w-100 d-inline-flex align-items-center justify-content-center" style="width: auto !important;">Area Staff</a>
    </div>
</nav>
