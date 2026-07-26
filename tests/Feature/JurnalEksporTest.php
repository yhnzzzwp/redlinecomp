<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\CartLine;
use App\Data\CheckoutData;
use App\Enums\MetodeBayar;
use App\Enums\TipeItem;
use App\Enums\TipePromo;
use App\Enums\TransaksiStatus;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Promo;
use App\Services\PosService;
use App\Services\ServiceTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Ekspor Jurnal Akuntansi: Owner-only, jurnal umum double-entry yang selalu
 * seimbang (debit = kredit), akun mengikuti config redline.akun, transaksi
 * Void dikecualikan, dan filter periode dihormati.
 */
final class JurnalEksporTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;

    private Pegawai $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('admin');
        $this->owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password'), 'role' => 'Owner', 'masih_bekerja' => true,
        ]);
        $this->kasir = Pegawai::create([
            'nama_pegawai' => 'Kasir', 'username' => 'kasir', 'email' => 'kasir@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    /** Unduh ekspor jurnal lalu muat kembali sebagai Spreadsheet. */
    private function muatJurnal(array $query = []): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $respon = $this->actingAs($this->owner)->get(route('analytics.jurnal', $query));
        $respon->assertOk();

        $tmp = tempnam(sys_get_temp_dir(), 'jurnal').'.xlsx';
        file_put_contents($tmp, $respon->streamedContent());

        try {
            return IOFactory::load($tmp);
        } finally {
            unlink($tmp);
        }
    }

    /** @return list<array<int, mixed>> baris data sheet Jurnal (tanpa header, tanpa baris TOTAL) */
    private function barisJurnal(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): array
    {
        $rows = $spreadsheet->getSheetByNameOrThrow('Jurnal')->toArray(null, true, false, false);
        array_shift($rows); // header

        return array_values(array_filter($rows, fn (array $r): bool => ($r[4] ?? '') !== 'TOTAL' && ($r[1] ?? '') !== null && ($r[1] ?? '') !== ''));
    }

    public function test_karyawan_tidak_bisa_mengunduh_jurnal(): void
    {
        $this->usePortal('staff');
        $this->actingAs($this->kasir)->get(route('analytics.jurnal'))->assertForbidden();
    }

    public function test_jurnal_kosong_tetap_valid(): void
    {
        $spreadsheet = $this->muatJurnal();

        $this->assertSame([], $this->barisJurnal($spreadsheet));
        $this->assertNotNull($spreadsheet->getSheetByName('Rekap Akun'));
        $this->assertNotNull($spreadsheet->getSheetByName('Info'));
    }

    public function test_jurnal_seimbang_dan_lengkap_untuk_penjualan_campuran(): void
    {
        $pos = app(PosService::class);

        // Produk bermodal: harga 1.000.000, modal 700.000 — dibeli 2 (QRIS + promo 10%).
        $produk = Produk::create([
            'nama_produk' => 'SSD Uji', 'jumlah_produk' => 10, 'harga' => 1_000_000,
            'harga_modal' => 700_000, 'show_katalog' => true,
        ]);
        Promo::create([
            'nama_promo' => 'Uji', 'kode_promo' => 'HEMAT10', 'tipe_promo' => TipePromo::Persen,
            'besar_promo' => 10, 'minimal_transaksi' => 0,
            'waktu_mulai' => now()->subDay(), 'waktu_berakhir' => now()->addDay(), 'aktif' => true,
        ]);
        $trxProduk = $pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 2)],
            metodeBayar: MetodeBayar::QRIS,
            bayar: 1_800_000,
            kodePromo: 'HEMAT10',
        ), $this->kasir);

        // Servis: jasa 250.000 + part dari stok (jual 100.000, modal 60.000) — Tunai.
        // POS menagih totalBiaya (jasa + part) sesuai janji ke customer.
        $part = Produk::create([
            'nama_produk' => 'Keyboard Part', 'jumlah_produk' => 3, 'harga' => 100_000,
            'harga_modal' => 60_000, 'show_katalog' => false,
        ]);
        $servis = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'Budi', 'nama_barang' => 'Laptop Uji', 'masalah' => 'Mati total',
        ], $this->kasir);
        $servis->update(['biaya_service' => 250_000]);
        app(ServiceTicketService::class)->tambahPart($servis, [
            'produk_id' => $part->id, 'nama_part' => 'Keyboard Part', 'jumlah' => 1, 'harga' => 100_000,
        ]);
        $trxServis = $pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Servis->value, $servis->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 350_000,
        ), $this->kasir);
        $this->assertSame(350_000, $trxServis->total); // jasa 250rb + part 100rb

        $baris = $this->barisJurnal($this->muatJurnal());

        // Seimbang: total debit = total kredit, dan tidak nol.
        $debit = array_sum(array_map(fn (array $r): int => (int) $r[5], $baris));
        $kredit = array_sum(array_map(fn (array $r): int => (int) $r[6], $baris));
        $this->assertSame($debit, $kredit);
        $this->assertGreaterThan(0, $debit);

        // Nilai per akun (kode dari config redline.akun).
        $jumlahPerAkun = [];
        foreach ($baris as $r) {
            $kode = (string) $r[2];
            $jumlahPerAkun[$kode]['debit'] = ($jumlahPerAkun[$kode]['debit'] ?? 0) + (int) $r[5];
            $jumlahPerAkun[$kode]['kredit'] = ($jumlahPerAkun[$kode]['kredit'] ?? 0) + (int) $r[6];
        }

        $akun = fn (string $kunci): string => (string) config("redline.akun.{$kunci}.kode");

        $this->assertSame(1_800_000, $jumlahPerAkun[$akun('qris')]['debit']);          // QRIS = total setelah diskon
        $this->assertSame(200_000, $jumlahPerAkun[$akun('diskon_penjualan')]['debit']); // promo 10% dari 2.000.000
        $this->assertSame(2_000_000, $jumlahPerAkun[$akun('penjualan_produk')]['kredit']);
        $this->assertSame(1_460_000, $jumlahPerAkun[$akun('hpp')]['debit']);            // 2×700rb produk + 60rb part servis
        $this->assertSame(1_460_000, $jumlahPerAkun[$akun('persediaan')]['kredit']);
        $this->assertSame(350_000, $jumlahPerAkun[$akun('kas')]['debit']);              // servis Tunai (jasa+part)
        $this->assertSame(350_000, $jumlahPerAkun[$akun('pendapatan_servis')]['kredit']);

        // No bukti kedua transaksi ikut tercantum.
        $bukti = array_map(fn (array $r): string => (string) $r[1], $baris);
        $this->assertContains($trxProduk->kode_nota, $bukti);
        $this->assertContains($trxServis->kode_nota, $bukti);
    }

    public function test_hpp_memakai_snapshot_bukan_harga_modal_terkini(): void
    {
        $pos = app(PosService::class);
        $produk = Produk::create([
            'nama_produk' => 'VGA Uji', 'jumlah_produk' => 5, 'harga' => 5_000_000,
            'harga_modal' => 4_000_000, 'show_katalog' => true,
        ]);
        $pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 1)],
            metodeBayar: MetodeBayar::Transfer,
            bayar: 5_000_000,
        ), $this->kasir);

        // Harga modal diedit SETELAH transaksi — jurnal tidak boleh berubah.
        $produk->update(['harga_modal' => 9_999_999]);

        $baris = $this->barisJurnal($this->muatJurnal());
        $hpp = array_sum(array_map(
            fn (array $r): int => (string) $r[2] === (string) config('redline.akun.hpp.kode') ? (int) $r[5] : 0,
            $baris,
        ));
        $this->assertSame(4_000_000, $hpp);
    }

    public function test_periode_lebih_dari_setahun_ditolak_dengan_pesan(): void
    {
        $this->actingAs($this->owner)
            ->get(route('analytics.jurnal', ['dari' => '2020-01-01', 'sampai' => '2026-12-31']))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_transaksi_void_dan_di_luar_periode_dikecualikan(): void
    {
        $pos = app(PosService::class);
        $produk = Produk::create([
            'nama_produk' => 'RAM Uji', 'jumlah_produk' => 10, 'harga' => 500_000, 'show_katalog' => true,
        ]);

        $void = $pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 500_000,
        ), $this->kasir);
        $void->update(['status' => TransaksiStatus::Void]);

        $kemarin = $pos->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 500_000,
        ), $this->kasir);
        // Query builder: melewati $fillable/timestamps untuk mundur-tanggal data uji.
        \App\Models\Transaksi::query()->whereKey($kemarin->id)->update(['created_at' => now()->subMonths(2)]);

        $baris = $this->barisJurnal($this->muatJurnal([
            'dari' => now()->startOfMonth()->format('Y-m-d'),
            'sampai' => now()->endOfMonth()->format('Y-m-d'),
        ]));

        $bukti = array_map(fn (array $r): string => (string) $r[1], $baris);
        $this->assertNotContains($void->kode_nota, $bukti);
        $this->assertNotContains($kemarin->kode_nota, $bukti);
        $this->assertSame([], $baris);
    }
}
