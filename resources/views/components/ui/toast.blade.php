{{-- Toast notification component — replaces static flash cards with auto-dismissing toasts --}}
@if (session('success') || session('error'))
<div x-data="{ show: true }" x-show="show" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-x-4"
     x-transition:enter-end="opacity-100 translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 translate-x-4"
     x-init="setTimeout(() => show = false, 4000)"
     class="rl-toast {{ session('success') ? 'rl-toast--success' : 'rl-toast--error' }}">
    <b>{!! session('success') ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-label="Sukses"><polyline points="20 6 9 17 4 12"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="Perhatian"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' !!}</b>
    <span>{{ session('success') ?? session('error') }}</span>
    <button type="button" class="rl-toast__close" aria-label="Tutup notifikasi" @click="show = false">&times;</button>
</div>
@endif
