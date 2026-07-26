<footer class="rl-footer mt-auto">
    <div class="rl-footer-container mx-auto">
        <div class="rl-ticks rl-ticks--dark mb-4"></div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="rl-logo fs-5" style="color:#f2f3f6">REDL<i>INE</i></span><span class="rl-stripe"></span>
                </div>
                <p class="rl-footer-desc">Pusat hardware komputer dan servis presisi. Dirakit, diuji, dan dikalibrasi langsung oleh teknisi kami di Salatiga.</p>
            </div>
            <div class="col-md-3">
                <h4>Layanan</h4>
                <a href="{{ route('catalogue') }}">Katalog Produk</a>
                <a href="{{ route('cek.servis') }}">Lacak Servis</a>
                <a href="{{ route('cek.nota') }}">Cek Nota Transaksi</a>
            </div>
            <div class="col-md-3">
                <h4>Bantuan</h4>
                <a href="{{ route('about') }}">Tentang Kami</a>
                <a href="https://wa.me/{{ config('redline.wa_number') }}" target="_blank" rel="noopener noreferrer">WhatsApp Kami</a>
            </div>
        </div>
        <div class="text-center mt-4 rl-footer-copy">&copy; {{ date('Y') }} Redline Komputer &middot; Jl. Diponegoro No. 52, Salatiga</div>
    </div>
</footer>
