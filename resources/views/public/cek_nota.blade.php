<x-layouts.public active="Service" title="Cek Nota Transaksi">
    @php
        $rp = fn ($n) => 'Rp '.number_format((int) $n, 0, ',', '.');
    @endphp

    <div class="rl-page-header">
        <h1 class="rl-page-title">Cek Nota Transaksi</h1>
        <p class="rl-page-desc">Verifikasi detail pembelian Anda di Redline Komputer.</p>
    </div>

    <div class="rl-body mx-auto" style="max-width:700px">
        <div class="rl-card p-4 mb-4 text-center">
            <h3 class="rl-section-title">Masukkan Kode Nota</h3>
            <form method="GET" action="{{ route('cek.nota') }}" class="d-flex justify-content-center gap-2 max-w-md mx-auto" style="max-width:400px">
                <input type="text" name="nota" value="{{ $nota }}" placeholder="Contoh: 123456" required
                       class="rl-input w-100 text-center" style="letter-spacing:1px;font-family:monospace">
                <button type="submit" class="btn-redline" style="padding:12px 24px">Cari</button>
            </form>
            @if(session('error'))
                <div class="mt-3 text-danger" style="font-size:13px">{{ session('error') }}</div>
            @endif
            <div class="mt-3">
                <a href="{{ route('cek.servis') }}" class="text-muted text-decoration-none" style="font-size:13px">Ingin melacak status servis? Klik di sini.</a>
            </div>
        </div>

        @if($transaksi)
            <div class="rl-card p-4">
                <div class="d-flex justify-content-between align-items-center pb-3 border-bottom mb-3" style="border-color:var(--line-2)!important">
                    <div>
                        <h2 class="fw-bold mb-1" style="font-size:20px;letter-spacing:-.4px">Detail Transaksi</h2>
                        <div class="text-muted tnum" style="font-size:13px">Nota #{{ $transaksi->kode_nota }}</div>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold" style="font-size:14px">{{ $transaksi->created_at->format('d M Y') }}</div>
                        <div class="text-muted" style="font-size:12px">{{ $transaksi->created_at->format('H:i') }} WIB</div>
                    </div>
                </div>

                <div class="row mb-4" style="font-size:13px">
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
                    <table class="w-100" style="font-size:13px">
                        <thead>
                            <tr class="text-muted border-bottom" style="border-color:var(--line-2)!important">
                                <th class="pb-2 fw-semibold" style="text-align:left">Item</th>
                                <th class="pb-2 fw-semibold" style="text-align:center">Qty</th>
                                <th class="pb-2 fw-semibold" style="text-align:right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transaksi->items as $item)
                                <tr>
                                    <td class="py-3" style="text-align:left">
                                        <div class="fw-semibold">{{ $item->nama_item }}</div>
                                        <div class="text-muted" style="font-size:11px">{{ $item->tipe->value ?? $item->tipe }}</div>
                                    </td>
                                    <td class="py-3 tnum" style="text-align:center">{{ $item->jumlah }}</td>
                                    <td class="py-3 tnum" style="text-align:right">{{ $rp($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-top pt-3 text-end" style="border-color:var(--line-2)!important;font-size:13px">
                    <div class="d-flex justify-content-end gap-4 mb-2">
                        <div class="text-muted">Subtotal</div>
                        <div class="tnum" style="width:100px">{{ $rp($transaksi->subtotal) }}</div>
                    </div>
                    @if ($transaksi->diskon > 0)
                        <div class="d-flex justify-content-end gap-4 mb-2">
                            <div class="text-muted">Diskon Promo</div>
                            <div class="tnum text-danger" style="width:100px">-{{ $rp($transaksi->diskon) }}</div>
                        </div>
                    @endif
                    <div class="d-flex justify-content-end gap-4 mb-3">
                        <div class="fw-bold fs-5">Total</div>
                        <div class="fw-bold fs-5 tnum" style="width:100px;color:var(--red-strong)">{{ $rp($transaksi->total) }}</div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-4 mb-1">
                        <div class="text-muted">Bayar</div>
                        <div class="tnum" style="width:100px">{{ $rp($transaksi->bayar) }}</div>
                    </div>
                    <div class="d-flex justify-content-end gap-4">
                        <div class="text-muted">Kembali</div>
                        <div class="tnum" style="width:100px">{{ $rp($transaksi->kembalian) }}</div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('pos.nota', $transaksi) }}" target="_blank" class="btn-ghost">Unduh PDF Nota</a>
            </div>
        @endif
    </div>
</x-layouts.public>
