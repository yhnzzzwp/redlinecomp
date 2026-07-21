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
    <b>{{ session('success') ? '✓' : '!' }}</b>
    <span>{{ session('success') ?? session('error') }}</span>
    <button type="button" class="rl-toast__close" @click="show = false">&times;</button>
</div>
@endif
