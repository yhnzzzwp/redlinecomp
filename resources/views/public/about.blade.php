<x-layouts.public active="About Us" title="Tentang Kami">
    <div class="rl-public-header">
        <div class="rl-kicker mb-2">Di balik <b>garis merah</b></div>
        <h1 class="rl-page-title">Tentang Redline Komputer</h1>
        <p class="rl-page-desc">Solusi terpercaya untuk kebutuhan IT Anda sejak 2016.</p>
        <div class="rl-ticks"></div>
    </div>

    <div class="rl-body rl-container-800 pb-5">
        {{-- Visi & Misi --}}
        <div class="rl-card p-4 p-md-5 mb-4" data-reveal>
            <div class="rl-kicker mb-2">Visi <b>&amp;</b> Misi</div>
            <h2 class="rl-title-lg mb-3">Arah &amp; Komitmen Kami</h2>
            <p class="rl-text-lead mb-4">
                Redline Komputer hadir dengan komitmen memberikan layanan IT terbaik bagi masyarakat.
                Kami percaya teknologi harus dapat diakses dan diandalkan oleh siapa saja, baik untuk
                kebutuhan personal maupun profesional.
            </p>

            <div class="row g-4">
                <div class="col-md-5">
                    <h3 class="rl-section-title mb-2">Visi</h3>
                    <p class="rl-text-desc mb-0">Menjadi pusat layanan dan penyedia solusi komputer yang terpercaya, inovatif, dan terdepan di Indonesia.</p>
                </div>
                <div class="col-md-7">
                    <h3 class="rl-section-title mb-2">Misi</h3>
                    <ol class="rl-misi">
                        <li>Menyediakan komponen komputer berkualitas tinggi dengan harga yang kompetitif.</li>
                        <li>Memberikan layanan servis yang transparan, cepat, dan bergaransi.</li>
                        <li>Mengutamakan kepuasan pelanggan melalui pelayanan yang ramah, jujur, dan profesional.</li>
                        <li>Berinovasi secara berkelanjutan mengikuti perkembangan teknologi terkini.</li>
                    </ol>
                </div>
            </div>
        </div>

        {{-- Lokasi & Kontak --}}
        <div class="row g-4 mb-4">
            <div class="col-md-6" data-reveal>
                <div class="rl-card p-4 h-100 text-center">
                    <span class="rl-feature-ico mx-auto mb-3">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <h3 class="rl-section-title mb-2">Lokasi Kami</h3>
                    <p class="text-muted mb-3 rl-text-sm rl-text-wrap">Jl. Diponegoro No. 52,
Salatiga</p>
                    <a href="https://maps.app.goo.gl/V5s33ckZDgTjSEz19" target="_blank" rel="noopener noreferrer" class="btn-ghost rl-btn-sm">Buka di Google Maps</a>
                </div>
            </div>
            <div class="col-md-6" data-reveal style="--reveal-d: 90ms">
                <div class="rl-card p-4 h-100 text-center">
                    <span class="rl-feature-ico mx-auto mb-3">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                    </span>
                    <h3 class="rl-section-title mb-2">Hubungi Kami</h3>
                    <p class="text-muted mb-3 rl-text-sm rl-text-wrap">WhatsApp: {{ config('redline.wa_number') }}
Email: redlinecomputer@gmail.com
Jam Buka: 09.00&ndash;18.00 WIB</p>
                    <a href="https://wa.me/{{ config('redline.wa_number') }}" target="_blank" rel="noopener noreferrer" class="btn-ghost rl-btn-sm">Chat Sekarang</a>
                </div>
            </div>
        </div>

        {{-- Lini baru --}}
        <div class="rl-card p-4 p-md-5 text-center" data-reveal>
            <div class="rl-kicker mb-2">Segera <b>hadir</b></div>
            <h2 class="rl-title-lg rl-text-red mb-2">Lini Baru: Toko Ikan</h2>
            <p class="rl-text-desc text-muted mb-4">Redline Komputer sedang memperluas layanan ke penjualan ikan. Pantau terus, segera hadir!</p>
            <a href="{{ route('toko-ikan') }}" class="btn-redline d-inline-block">Kunjungi Toko Ikan</a>
        </div>
    </div>
</x-layouts.public>
