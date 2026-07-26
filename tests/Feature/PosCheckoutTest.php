<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\CartLine;
use App\Data\CheckoutData;
use App\Enums\MetodeBayar;
use App\Enums\TipeItem;
use App\Enums\TipePromo;
use App\Exceptions\PembayaranKurangException;
use App\Exceptions\PromoTidakValidException;
use App\Exceptions\StokTidakCukupException;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Promo;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private PosService $pos;

    private Pegawai $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pos = app(PosService::class);
        $this->kasir = Pegawai::create([
            'nama_pegawai' => 'Kasir Uji', 'username' => 'kasir', 'email' => 'kasir@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    private function produk(int $stok, int $harga): Produk
    {
        return Produk::create([
            'nama_produk' => 'Uji Produk', 'jumlah_produk' => $stok, 'harga' => $harga, 'show_katalog' => true,
        ]);
    }

    public function test_checkout_menghitung_total_dan_mengurangi_stok(): void
    {
        $produk = $this->produk(5, 1_000_000);

        $trx = $this->pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 2)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 2_000_000,
        ), $this->kasir);

        $this->assertSame(2_000_000, $trx->total);
        $this->assertSame(0, $trx->kembalian);
        $this->assertSame(3, $produk->fresh()->jumlah_produk);
        $this->assertCount(1, $trx->items);
        $this->assertDatabaseHas('transaksi', ['kode_nota' => $trx->kode_nota, 'total' => 2_000_000]);
    }

    public function test_checkout_servis_mencatat_riwayat_status_diambil(): void
    {
        $servis = app(\App\Services\ServiceTicketService::class)->buat([
            'nama_customer' => 'Budi', 'nama_barang' => 'Laptop Uji', 'masalah' => 'Mati total',
        ], $this->kasir);
        $servis->update(['biaya_service' => 100_000]);

        $trx = $this->pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Servis->value, $servis->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 100_000,
        ), $this->kasir);

        // Jejak pengambilan via POS tercatat di timeline riwayat (SRS §2.5).
        $this->assertDatabaseHas('service_status', [
            'service_id' => $servis->id,
            'status' => \App\Enums\StatusService::SudahDiambil->value,
            'pegawai_id' => $this->kasir->id,
            'catatan' => 'Dibayar & diambil via POS — Nota #' . $trx->kode_nota,
        ]);
    }

    public function test_checkout_menolak_saat_stok_tidak_cukup(): void
    {
        $produk = $this->produk(1, 1_000_000);

        try {
            $this->pos->checkout(new CheckoutData(
                items: [new CartLine(TipeItem::Produk->value, $produk->id, 2)],
                metodeBayar: MetodeBayar::Tunai,
                bayar: 2_000_000,
            ), $this->kasir);
            $this->fail('Seharusnya menolak stok tidak cukup.');
        } catch (StokTidakCukupException) {
            $this->assertSame(1, $produk->fresh()->jumlah_produk);
            $this->assertDatabaseCount('transaksi', 0);
        }
    }

    public function test_checkout_menerapkan_promo_persen_dengan_batas_maksimal(): void
    {
        $produk = $this->produk(10, 25_000_000);
        Promo::create([
            'nama_promo' => 'Uji', 'kode_promo' => 'GAMING40', 'tipe_promo' => TipePromo::Persen,
            'besar_promo' => 40, 'minimal_transaksi' => 5_000_000, 'maksimal_diskon' => 2_000_000,
            'waktu_mulai' => now()->subDay(), 'waktu_berakhir' => now()->addDay(), 'aktif' => true,
        ]);

        $trx = $this->pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 1)],
            metodeBayar: MetodeBayar::QRIS,
            bayar: 23_000_000,
            kodePromo: 'GAMING40',
        ), $this->kasir);

        $this->assertSame(2_000_000, $trx->diskon);
        $this->assertSame(23_000_000, $trx->total);
    }

    public function test_checkout_menolak_promo_di_bawah_minimal_transaksi(): void
    {
        $produk = $this->produk(10, 1_000_000);
        Promo::create([
            'nama_promo' => 'Uji', 'kode_promo' => 'BIG', 'tipe_promo' => TipePromo::Persen,
            'besar_promo' => 10, 'minimal_transaksi' => 5_000_000,
            'waktu_mulai' => now()->subDay(), 'waktu_berakhir' => now()->addDay(), 'aktif' => true,
        ]);

        $this->expectException(PromoTidakValidException::class);
        $this->pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 1_000_000,
            kodePromo: 'BIG',
        ), $this->kasir);
    }

    public function test_checkout_menolak_pembayaran_kurang(): void
    {
        $produk = $this->produk(10, 5_000_000);

        $this->expectException(PembayaranKurangException::class);
        $this->pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 1_000_000,
        ), $this->kasir);
    }
}
