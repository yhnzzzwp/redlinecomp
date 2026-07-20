<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redline Komputer — Hardware & Servis Premium</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <nav class="rl-pubnav">
        <span class="rl-logo fs-5">REDL<i>INE</i></span>
        <a href="{{ route('landing') }}" class="active">Home</a>
        <a href="#">About Us</a>
        <a href="#">Catalogue</a>
        <a href="#">Service</a>
        <a href="{{ route('login') }}" class="ms-auto btn-ghost" style="padding:8px 16px">Login Staff</a>
    </nav>

    <section class="rl-hero text-center">
        <span class="rl-pill red d-inline-flex mb-3" style="background:rgba(255,255,255,.1);color:#ff8b8e">HIGH PERFORMANCE HARDWARE</span>
        <h1 class="fw-bold" style="font-size:38px;letter-spacing:-.6px">Upgrade Performa<br><span style="color:var(--red)">Komputer Anda</span></h1>
        <p class="mx-auto mt-3" style="max-width:52ch;color:#c7d2df;font-size:14px">Rasakan kecepatan ekstrim dengan teknologi hardware terbaru. Presisi tinggi untuk gaming profesional dan produktivitas tanpa hambatan.</p>
        <div class="d-flex gap-2 justify-content-center mt-4">
            <a href="#" class="btn-redline">Belanja Sekarang 🛒</a>
            <a href="#" class="btn-ghost">Lihat Katalog</a>
        </div>
    </section>

    <section class="p-4" style="max-width:1100px;margin:0 auto">
        <h2 class="fw-bold mb-3" style="font-size:22px">Katalog Produk</h2>
        <div class="row g-3">
            @foreach (\App\Models\Produk::where('show_katalog', true)->limit(8)->get() as $p)
                <div class="col-6 col-md-3">
                    <div class="rl-card h-100 overflow-hidden">
                        <div style="height:120px;background:linear-gradient(135deg,#1b1e23,#3a3e45)"></div>
                        <div class="p-3">
                            <div class="fw-semibold" style="font-size:13.5px">{{ $p->nama_produk }}</div>
                            <div class="fw-bold mt-1" style="color:var(--red-strong)">Rp {{ number_format($p->harga,0,',','.') }}</div>
                            <span class="rl-pill {{ $p->jumlah_produk==0?'red':($p->jumlah_produk<=5?'amber':'green') }} mt-2" style="font-size:9px">{{ $p->statusStok() }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <footer class="rl-footer mt-4">
        <div class="row g-4" style="max-width:1100px;margin:0 auto">
            <div class="col-md-6">
                <h4>Redline Komputer</h4>
                <p style="font-size:12.5px;color:#a9b7c7;max-width:34ch">Pusat perangkat keras komputer premium. Menghadirkan presisi, efisiensi, dan kecanggihan teknologi terbaru.</p>
            </div>
            <div class="col-md-3"><h4>Services</h4><a href="#">Custom PC Build</a><a href="#">Reparasi Hardware</a><a href="#">Cek Garansi</a></div>
            <div class="col-md-3"><h4>Help Center</h4><a href="#">Panduan Belanja</a><a href="#">FAQ</a><a href="#">Hubungi Kami</a></div>
        </div>
        <div class="text-center mt-4" style="font-size:11.5px;color:#8496a8">© 2026 Redline Komputer. Semua Hak Dilindungi.</div>
    </footer>
</body>
</html>
