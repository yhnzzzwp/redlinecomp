<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\CartLine;
use App\Data\CheckoutData;
use App\Enums\MetodeBayar;
use App\Enums\TipeItem;
use App\Enums\TipeMutasiStok;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Services\PosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class StokTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped(
            'Fitur mutasi stok sudah dihapus: migrasi 2026_08_20_000008 men-drop '
            .'tabel mutasi_stok, migrasi 2026_08_20_000003 menghapus kolom '
            .'harga/jumlah_produk dari produk, rute stok.* tidak lagi terdaftar, '
            .'dan StokService kini kelas kosong. Tes ini sengaja dipertahankan '
            .'sebagai catatan perilaku lama. Hidupkan kembali bila fitur stok '
            .'dikembalikan, atau hapus berkas ini beserta StokController, '
            .'StokService, dan JurnalExcelService yang sudah tidak terpakai.'
        );

        $this->usePortal('staff');
        $this->staff = Pegawai::create([
            'nama_pegawai' => 'Staff Uji', 'username' => 'staff', 'email' => 'staff@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_halaman_opname_menampilkan_produk(): void
    {
        Produk::create(['nama_produk' => 'RAM Uji', 'sku' => 'RAM-1', 'harga' => 500_000, 'jumlah_produk' => 7, 'show_katalog' => true]);

        $this->actingAs($this->staff)->get(route('stok.opname'))
            ->assertOk()
            ->assertSee('Stok Opname')
            ->assertSee('RAM Uji');
    }

    public function test_opname_menyesuaikan_stok_dan_mencatat_mutasi(): void
    {
        $a = Produk::create(['nama_produk' => 'A', 'sku' => 'A-1', 'harga' => 1000, 'jumlah_produk' => 10]);
        $b = Produk::create(['nama_produk' => 'B', 'sku' => 'B-1', 'harga' => 1000, 'jumlah_produk' => 5]);

        $this->actingAs($this->staff)->post(route('stok.opname.simpan'), [
            'stok' => [$a->id => 8, $b->id => 5],
            'catatan' => 'akhir bulan',
        ])->assertRedirect(route('stok.opname'))->assertSessionHas('success');

        $this->assertSame(8, $a->fresh()->jumlah_produk);
        $this->assertSame(5, $b->fresh()->jumlah_produk);

        // Hanya baris berselisih yang tercatat.
        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $a->id, 'tipe' => TipeMutasiStok::Opname->value,
            'jumlah_sebelum' => 10, 'selisih' => -2, 'jumlah_sesudah' => 8,
            'pegawai_id' => $this->staff->id,
        ]);
        $this->assertDatabaseMissing('mutasi_stok', ['produk_id' => $b->id]);
    }

    public function test_checkout_pos_mencatat_mutasi_penjualan(): void
    {
        $produk = Produk::create(['nama_produk' => 'GPU Uji', 'sku' => 'GPU-1', 'harga' => 1_000_000, 'jumlah_produk' => 5]);

        $trx = app(PosService::class)->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 2)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 2_000_000,
        ), $this->staff);

        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $produk->id, 'tipe' => TipeMutasiStok::Penjualan->value,
            'jumlah_sebelum' => 5, 'selisih' => -2, 'jumlah_sesudah' => 3,
            'keterangan' => 'Nota #' . $trx->kode_nota, 'pegawai_id' => $this->staff->id,
        ]);
    }

    public function test_void_mengembalikan_stok_dan_tercatat(): void
    {
        $this->usePortal('admin');
        $owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password'), 'role' => 'Owner', 'masih_bekerja' => true,
        ]);
        $produk = Produk::create(['nama_produk' => 'SSD Uji', 'sku' => 'SSD-1', 'harga' => 900_000, 'jumlah_produk' => 4]);

        $trx = app(PosService::class)->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Produk->value, $produk->id, 3)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: 2_700_000,
        ), $owner);

        $this->actingAs($owner)->post(route('transaksi.void', $trx))->assertRedirect();

        $this->assertSame(4, $produk->fresh()->jumlah_produk);
        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $produk->id, 'tipe' => TipeMutasiStok::Void->value,
            'selisih' => 3, 'keterangan' => 'Void nota #' . $trx->kode_nota,
        ]);
    }

    public function test_edit_produk_mencatat_penyesuaian_hanya_bila_stok_berubah(): void
    {
        $produk = Produk::create(['nama_produk' => 'PSU Uji', 'sku' => 'PSU-1', 'harga' => 700_000, 'jumlah_produk' => 6]);

        // Ubah harga saja — tidak ada mutasi.
        $this->actingAs($this->staff)->put(route('produk.update', $produk), [
            'nama_produk' => 'PSU Uji', 'sku' => 'PSU-1', 'harga' => 750_000, 'jumlah_produk' => 6,
        ]);
        $this->assertDatabaseMissing('mutasi_stok', ['produk_id' => $produk->id, 'tipe' => TipeMutasiStok::Penyesuaian->value]);

        // Ubah stok — tercatat.
        $this->actingAs($this->staff)->put(route('produk.update', $produk), [
            'nama_produk' => 'PSU Uji', 'sku' => 'PSU-1', 'harga' => 750_000, 'jumlah_produk' => 9,
        ]);
        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $produk->id, 'tipe' => TipeMutasiStok::Penyesuaian->value,
            'jumlah_sebelum' => 6, 'selisih' => 3, 'jumlah_sesudah' => 9, 'keterangan' => 'Edit produk',
        ]);
    }

    public function test_impor_excel_mencatat_mutasi_impor(): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['nama_produk', 'sku', 'kategori', 'harga', 'harga_modal', 'jumlah_produk', 'deskripsi'], null, 'A1');
        $sheet->fromArray([['Webcam Uji', 'WC-01', '', 300000, 250000, 11, '']], null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'uji-stok') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
        $file = new \Illuminate\Http\UploadedFile($path, 'produk.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file])
            ->assertSessionHas('success');

        $produk = Produk::query()->where('sku', 'WC-01')->firstOrFail();
        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $produk->id, 'tipe' => TipeMutasiStok::Impor->value,
            'jumlah_sebelum' => 0, 'jumlah_sesudah' => 11,
        ]);
    }

    public function test_part_servis_mencatat_mutasi_pasang_dan_batal(): void
    {
        $produk = Produk::create(['nama_produk' => 'PSU Part', 'sku' => 'PSU-1', 'harga' => 800_000, 'harga_modal' => 650_000, 'jumlah_produk' => 4]);
        $svc = app(\App\Services\ServiceTicketService::class);
        $servis = $svc->buat($this->dataServis('Andi', 'PC Rakitan', 'PSU mati'), $this->staff);

        $part = $svc->tambahPart($servis, ['produk_id' => $produk->id, 'nama_part' => 'PSU Part', 'jumlah' => 2, 'harga' => 800_000]);

        $this->assertSame(2, $produk->fresh()->jumlah_produk);
        $this->assertSame(650_000, $part->harga_modal); // snapshot modal saat dipasang
        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $produk->id, 'tipe' => TipeMutasiStok::PartServis->value,
            'jumlah_sebelum' => 4, 'jumlah_sesudah' => 2,
            'keterangan' => 'Part servis ' . $servis->nomor_resi,
        ]);

        $svc->hapusPart($servis, $part);

        $this->assertSame(4, $produk->fresh()->jumlah_produk);
        $this->assertDatabaseHas('mutasi_stok', [
            'produk_id' => $produk->id, 'tipe' => TipeMutasiStok::PartServis->value,
            'jumlah_sebelum' => 2, 'jumlah_sesudah' => 4,
            'keterangan' => 'Part servis dibatalkan ' . $servis->nomor_resi,
        ]);
    }

    public function test_halaman_mutasi_menampilkan_riwayat_dan_filter_tipe(): void
    {
        $produk = Produk::create(['nama_produk' => 'Kabel Uji', 'sku' => 'KB-1', 'harga' => 50_000, 'jumlah_produk' => 3]);
        app(\App\Services\StokService::class)->catat($produk, 3, 10, TipeMutasiStok::Opname, 'Opname fisik', $this->staff->id);

        $this->actingAs($this->staff)->get(route('stok.mutasi'))
            ->assertOk()
            ->assertSee('Riwayat Mutasi Stok')
            ->assertSee('Kabel Uji')
            ->assertSee('Opname fisik');

        $this->actingAs($this->staff)->get(route('stok.mutasi', ['tipe' => 'Penjualan']))
            ->assertOk()
            ->assertDontSee('Kabel Uji');
    }
}
