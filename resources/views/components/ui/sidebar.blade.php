@props(['active' => ''])
@php
    $user = auth()->user();
    $nav = [
        ['dashboard', 'Dashboard', route('dashboard'), false, 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
        ['pos', 'POS', route('pos'), false, 'M3 4h18v12H3zM3 20h18'],
        ['produk', 'Produk', route('produk.index'), false, 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7'],
        ['transaksi', 'Transaksi', route('transaksi.index'), false, 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['analytics', 'Analytics', route('analytics'), true, 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
        ['service', 'Servis', route('service'), false, 'M14 6a4 4 0 005 5l-8 8-3-3 6-6a4 4 0 010-4z'],
        ['promo', 'Promo', route('promo.index'), true, 'M3 12l8-8h8v8l-8 8-8-8zM15 9h.01'],
        ['pegawai', 'Akun Pegawai', route('pegawai.index'), true, 'M3 5h18v14H3zM9 12a2 2 0 100-4 2 2 0 000 4z'],
    ];
@endphp
<aside class="rl-side" role="navigation">
    <div class="rl-side__brand">
        <span class="rl-logo fs-5">REDL<i>INE</i></span><span class="rl-stripe"></span>
    </div>

    @foreach ($nav as [$key, $label, $url, $ownerOnly, $path])
        @if (! $ownerOnly || ($user && $user->isOwner()))
            <a href="{{ $url }}" @click="mobileNav = false" class="rl-nav-item {{ $active === $key ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                <span>{{ $label }}</span>
            </a>
        @endif
    @endforeach

    <div class="rl-side__foot">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rl-nav-item w-100 border-0 bg-transparent text-start">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
