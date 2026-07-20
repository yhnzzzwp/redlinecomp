<x-layouts.public active="About Us" title="Tentang Kami">
    <div style="background:var(--navy);color:#fff;padding:60px 34px;text-align:center">
        <h1 class="fw-bold mb-3" style="font-size:32px;letter-spacing:-1px">Tentang Redline Komputer</h1>
        <p class="text-muted mx-auto" style="max-width:500px;font-size:15px;color:#a9b7c7!important">Solusi terpercaya untuk kebutuhan IT Anda sejak 2015.</p>
    </div>

    <div class="rl-body mx-auto" style="max-width:800px;padding-top:40px">
        <div class="rl-card p-4 p-md-5 mb-4">
            <h2 class="fw-bold mb-4" style="font-size:22px;letter-spacing:-.5px;color:var(--red-strong)">Visi & Misi</h2>
            
            <p style="font-size:15px;line-height:1.7;margin-bottom:20px">
                Redline Komputer hadir dengan komitmen untuk memberikan layanan IT terbaik bagi masyarakat. Kami percaya bahwa teknologi harus dapat diakses dan diandalkan oleh siapa saja, baik untuk kebutuhan personal maupun profesional.
            </p>
            
            <h4 class="fw-bold mt-4 mb-3" style="font-size:16px">Visi Kami</h4>
            <p style="font-size:14px;line-height:1.6;color:var(--text-muted)">Menjadi pusat layanan dan penyedia solusi komputer terdepan yang terpercaya dan inovatif di Indonesia.</p>
            
            <h4 class="fw-bold mt-4 mb-3" style="font-size:16px">Misi Kami</h4>
            <ul style="font-size:14px;line-height:1.6;color:var(--text-muted);padding-left:20px">
                <li class="mb-2">Menyediakan komponen komputer berkualitas tinggi dengan harga kompetitif.</li>
                <li class="mb-2">Memberikan layanan servis dan perbaikan yang transparan, cepat, dan bergaransi.</li>
                <li class="mb-2">Mengedepankan kepuasan pelanggan melalui pelayanan yang ramah dan profesional.</li>
                <li>Terus berinovasi dan mengikuti perkembangan teknologi terkini.</li>
            </ul>
        </div>
        
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="rl-card p-4 h-100 text-center">
                    <div class="mb-3" style="font-size:32px">📍</div>
                    <h3 class="fw-bold mb-2" style="font-size:16px">Lokasi Kami</h3>
                    <p class="text-muted mb-0" style="font-size:13px;line-height:1.5">
                        Jl. Teknologi No. 42<br>
                        Kawasan Digital Center<br>
                        Jakarta 12345
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="rl-card p-4 h-100 text-center">
                    <div class="mb-3" style="font-size:32px">💬</div>
                    <h3 class="fw-bold mb-2" style="font-size:16px">Hubungi Kami</h3>
                    <p class="text-muted mb-3" style="font-size:13px;line-height:1.5">
                        WhatsApp: {{ config('redline.wa_number') }}<br>
                        Email: halo@redlinekomputer.id<br>
                        Jam Buka: 09:00 - 18:00
                    </p>
                    <a href="https://wa.me/{{ config('redline.wa_number') }}" target="_blank" class="btn-ghost btn-sm">Chat Sekarang</a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
