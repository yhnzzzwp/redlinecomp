<footer class="rl-footer mt-4">
    <div class="row g-4" style="max-width:1100px;margin:0 auto">
        <div class="col-md-6">
            <h4>Redline Komputer</h4>
            <p style="font-size:12.5px;color:#a9b7c7;max-width:34ch">Pusat perangkat keras komputer premium. Menghadirkan presisi, efisiensi, dan kecanggihan teknologi terbaru.</p>
        </div>
        <div class="col-md-3">
            <h4>Services</h4>
            <a href="{{ route('cek.servis') }}">Cek Servis</a>
            <a href="{{ route('catalogue') }}">Custom PC Build</a>
            <a href="{{ route('catalogue') }}">Reparasi Hardware</a>
        </div>
        <div class="col-md-3">
            <h4>Help Center</h4>
            <a href="{{ route('about') }}">Tentang Kami</a>
            <a href="{{ route('cek.servis') }}">Lacak Servis</a>
            <a href="{{ route('login') }}">Login Staff</a>
        </div>
    </div>
    <div class="text-center mt-4" style="font-size:11.5px;color:#8496a8">&copy; {{ date('Y') }} Redline Komputer. Semua Hak Dilindungi.</div>
</footer>
