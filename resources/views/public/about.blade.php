<x-layouts.public active="About Us" title="Tentang Kami">
    <div class="rl-public-header">
        <h1 class="rl-page-title">Tentang Redline Komputer</h1>
        <p class="rl-page-desc">Solusi terpercaya untuk kebutuhan IT Anda sejak 2015.</p>
    </div>

    <div class="rl-body rl-container-800 my-5 pb-5">
        <div class="rl-card p-4 p-md-5 mb-4">
            <h2 class="rl-title-lg rl-text-red mb-4">Visi & Misi</h2>
            
            <p class="rl-text-lead mb-4">
                Redline Komputer hadir dengan komitmen untuk memberikan layanan IT terbaik bagi masyarakat. Kami percaya bahwa teknologi harus dapat diakses dan diandalkan oleh siapa saja, baik untuk kebutuhan personal maupun profesional.
            </p>
            
            <h4 class="rl-section-title mt-4 mb-3">Visi Kami</h4>
            <p class="rl-text-desc text-muted">Menjadi pusat layanan dan penyedia solusi komputer terdepan yang terpercaya dan inovatif di Indonesia.</p>
            
            <h4 class="rl-section-title mt-4 mb-3">Misi Kami</h4>
            <ul class="rl-text-desc text-muted ps-4">
                <li class="mb-2">Menyediakan komponen komputer berkualitas tinggi dengan harga kompetitif.</li>
                <li class="mb-2">Memberikan layanan servis dan perbaikan yang transparan, cepat, dan bergaransi.</li>
                <li class="mb-2">Mengedepankan kepuasan pelanggan melalui pelayanan yang ramah dan profesional.</li>
                <li>Terus berinovasi dan mengikuti perkembangan teknologi terkini.</li>
            </ul>
        </div>
        
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="rl-card p-4 h-100 text-center">
                    <div class="mb-3 rl-icon-md">📍</div>
                    <h3 class="rl-section-title mb-2">Lokasi Kami</h3>
                    <p class="text-muted mb-0 rl-text-sm rl-text-wrap">Jl. Teknologi No. 42
Kawasan Digital Center
Jakarta 12345</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="rl-card p-4 h-100 text-center">
                    <div class="mb-3 rl-icon-md">💬</div>
                    <h3 class="rl-section-title mb-2">Hubungi Kami</h3>
                    <p class="text-muted mb-3 rl-text-sm rl-text-wrap">WhatsApp: {{ config('redline.wa_number') }}
Email: halo@redlinekomputer.id
Jam Buka: 09:00 - 18:00</p>
                    <a href="https://wa.me/{{ config('redline.wa_number') }}" target="_blank" class="btn-ghost rl-btn-sm">Chat Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
