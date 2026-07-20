@props(['active' => 'Home'])
<nav class="rl-pubnav">
    <span class="rl-logo fs-5">REDL<i>INE</i></span>
    <a href="{{ route('landing') }}" @class(['active' => $active === 'Home'])>Home</a>
    <a href="{{ route('about') }}" @class(['active' => $active === 'About Us'])>About Us</a>
    <a href="{{ route('catalogue') }}" @class(['active' => $active === 'Catalogue'])>Catalogue</a>
    <a href="{{ route('cek.servis') }}" @class(['active' => $active === 'Service'])>Service</a>
    <a href="{{ route('login') }}" class="ms-auto btn-ghost" style="padding:8px 16px">Login Staff</a>
</nav>
