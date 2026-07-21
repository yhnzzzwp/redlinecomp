<x-layouts.public :active="$aktif" :title="$judul">
    <section class="rl-soon-page">
        <div>
            <div class="rl-stripe-lg"></div>
            <h1 class="rl-page-title">{{ $judul }}</h1>
            <p class="text-muted mt-2 rl-desc-sm">Halaman ini sedang dalam pengembangan dan akan segera hadir.</p>
            <a href="{{ route('landing') }}" class="btn-redline d-inline-block mt-3">&larr; Kembali ke Beranda</a>
        </div>
    </section>
</x-layouts.public>
