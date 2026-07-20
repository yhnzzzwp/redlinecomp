<x-layouts.public :active="$aktif" :title="$judul">
    <section style="min-height:440px;display:grid;place-items:center;text-align:center;padding:60px 24px">
        <div>
            <div class="rl-stripe" style="width:84px;height:6px;margin:0 auto 18px"></div>
            <h1 class="fw-bold" style="font-size:30px;letter-spacing:-.5px">{{ $judul }}</h1>
            <p class="text-muted mt-2" style="max-width:44ch;margin-inline:auto">Halaman ini sedang dalam pengembangan dan akan segera hadir.</p>
            <a href="{{ route('landing') }}" class="btn-redline d-inline-block mt-3">&larr; Kembali ke Beranda</a>
        </div>
    </section>
</x-layouts.public>
