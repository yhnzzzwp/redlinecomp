<x-layouts.public active="Service" title="Cek Nota Transaksi">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-public-header">
        <h1 class="rl-page-title">Cek Nota Transaksi</h1>
        <p class="rl-page-desc">Verifikasi detail pembelian Anda di Redline Komputer.</p>
    </div>

    <div class="rl-body rl-container-700 my-5 pb-5">
        <div class="rl-card p-4 mb-4 text-center">
            <h3 class="rl-section-title">Masukkan Kode Nota</h3>
            <form method="GET" action="{{ route('cek.nota') }}" class="d-flex justify-content-center gap-2 mx-auto rl-w-400px">
                <input type="text" name="nota" value="{{ $nota }}" placeholder="Contoh: 123456" required
                       class="rl-input w-100 text-center rl-input-mono">
                <button type="submit" class="btn-redline rl-btn-lg">Cari</button>
            </form>
            @if(session('error'))
                <div class="mt-3 text-danger rl-text-sm">{{ session('error') }}</div>
            @endif
            <div class="mt-3">
                <a href="{{ route('cek.servis') }}" class="text-muted text-decoration-none rl-text-sm">Ingin melacak status servis? Klik di sini.</a>
            </div>
        </div>

        @if($transaksi)
            <div class="rl-card p-4">
                <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3 rl-border-light">
                    <div>
                        <h2 class="rl-title-md mb-1">Detail Transaksi</h2>
                        <div class="text-muted tnum rl-text-sm">Nota #{{ $transaksi->kode_nota }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold rl-text-md">{{ $transaksi->created_at->format('d M Y') }}</div>
                        <div class="text-muted rl-text-sm">{{ $transaksi->created_at->format('H:i') }} WIB</div>
                    </div>
                </div>

                <div class="row mb-4 rl-text-sm">
                    <div class="col-6">
                        <div class="text-muted mb-1">Pelanggan</div>
                        <div class="fw-semibold">{{ $transaksi->nama_pembeli ?? 'Pelanggan Umum' }}</div>
                        @if($transaksi->nomor_hp_pembeli)
                            <div class="text-muted">{{ $transaksi->nomor_hp_pembeli }}</div>
                        @endif
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted mb-1">Metode Pembayaran</div>
                        <span class="rl-pill {{ $transaksi->metode_bayar === 'Tunai' ? 'green' : 'blue' }}">{{ $transaksi->metode_bayar }}</span>
                    </div>
                </div>

                <div class="mb-4">
                    <table class="w-100 rl-text-sm">
                        <thead>
                            <tr class="text-muted border-bottom rl-border-light">
                                <th class="pb-2 fw-semibold text-start">Item</th>
                                <th class="pb-2 fw-semibold text-center">Qty</th>
                                <th class="pb-2 fw-semibold text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaksi->items as $item)
                                <tr>
                                    <td class="py-3 text-start">
                                        <div class="fw-semibold">{{ $item->nama_item }}</div>
                                        <div class="text-muted rl-text-xs">{{ $item->tipe->value ?? $item->tipe }}</div>
                                    </td>
                                    <td class="py-3 tnum text-center">{{ $item->jumlah }}</td>
                                    <td class="py-3 tnum text-end">{{ $rp($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-top pt-3 text-end rl-border-light rl-text-sm">
                    <div class="d-flex justify-content-end gap-4 mb-2">
                        <div class="text-muted">Subtotal</div>
                        <div class="tnum rl-w-100px">{{ $rp($transaksi->subtotal) }}</div>
                    </div>
                    @if ($transaksi->diskon > 0)
                        <div class="d-flex justify-content-end gap-4 mb-2">
                            <div class="text-muted">Diskon Promo</div>
                            <div class="tnum text-danger rl-w-100px">-{{ $rp($transaksi->diskon) }}</div>
                        </div>
                    @endif
                    <div class="d-flex justify-content-end gap-4 mb-3">
                        <div class="fw-bold fs-5">Total</div>
                        <div class="fw-bold fs-5 tnum rl-text-total rl-w-100px">{{ $rp($transaksi->total) }}</div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-4 mb-1">
                        <div class="text-muted">Bayar</div>
                        <div class="tnum rl-w-100px">{{ $rp($transaksi->bayar) }}</div>
                    </div>
                    <div class="d-flex justify-content-end gap-4">
                        <div class="text-muted">Kembali</div>
                        <div class="tnum rl-w-100px">{{ $rp($transaksi->kembalian) }}</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('pos.nota', $transaksi) }}" target="_blank" class="btn-ghost">Unduh PDF Nota</a>
            </div>
        @endif
    </div>
</x-layouts.public>
