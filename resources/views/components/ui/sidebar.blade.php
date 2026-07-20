@props(['active' => ''])
@php
    $user = auth()->user();
    $nav = [
        ['dashboard', 'Dashboard', route('dashboard'), false, 'M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
        ['pos', 'POS', route('pos'), false, 'M3 4h18v12H3zM3 20h18'],
        ['produk', 'Products', route('produk.index'), false, 'M3 7l9-4 9 4-9 4-9-4zM3 7v10l9 4 9-4V7'],
        ['analytics', 'Analytics', route('analytics'), true, 'M4 20V10M10 20V4M16 20v-7M22 20H2'],
        ['customers', 'Customers', route('customers'), false, 'M9 8a3 3 0 100-6 3 3 0 000 6zM3 20c0-3.3 2.7-5 6-5s6 1.7 6 5'],
        ['service', 'Service', route('service'), false, 'M14 6a4 4 0 005 5l-8 8-3-3 6-6a4 4 0 010-4z'],
        ['promo', 'Promo', route('promo.index'), true, 'M3 12l8-8h8v8l-8 8-8-8zM15 9h.01'],
        ['pegawai', 'Akun Pegawai', route('pegawai'), true, 'M3 5h18v14H3zM9 12a2 2 0 100-4 2 2 0 000 4z'],
    ];
@endphp
<aside class="rl-side">
    <div class="rl-side__brand">
        <span class="rl-logo fs-5">REDL<i>INE</i></span><span class="rl-stripe"></span>
    </div>

    @foreach ($nav as [$key, $label, $url, $ownerOnly, $path])
        @if (! $ownerOnly || ($user && $user->isOwner()))
            <a href="{{ $url }}" class="rl-nav-item {{ $active === $key ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"/></svg>
                <span>{{ $label }}</span>
                @if ($ownerOnly) <span class="rl-nav-owner">Owner</span> @endif
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
