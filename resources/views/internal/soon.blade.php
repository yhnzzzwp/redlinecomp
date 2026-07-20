<x-layouts.app :active="$aktif" :title="$judul">
    <div class="rl-card p-5 text-center">
        <div class="rl-stripe" style="width:84px;height:6px;margin:0 auto 18px"></div>
        <h1 class="fw-bold" style="font-size:24px;letter-spacing:-.4px">{{ $judul }}</h1>
        <p class="text-muted mt-2" style="max-width:46ch;margin-inline:auto">Modul ini sedang dalam pengembangan. Fondasinya sudah siap dan akan diisi pada tahap berikutnya.</p>
    </div>
</x-layouts.app>
