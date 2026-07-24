<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProdukCrudTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = Pegawai::create([
            'nama_pegawai' => 'Staff Uji', 'username' => 'staff', 'email' => 'staff@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_tamu_tidak_bisa_akses_manajemen_produk(): void
    {
        $this->get(route('produk.index'))->assertRedirect(route('login'));
    }

    public function test_staff_bisa_menambah_produk_dengan_foto(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->staff)->post(route('produk.store'), [
            'nama_produk' => 'RTX 5090', 'sku' => 'RL-NV-5090', 'harga' => 30_000_000,
            'jumlah_produk' => 4, 'show_katalog' => '1',
            'foto' => UploadedFile::fake()->image('rtx.jpg'),
        ]);

        $response->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('produk', ['sku' => 'RL-NV-5090', 'harga' => 30_000_000, 'jumlah_produk' => 4]);
        $produk = Produk::query()->where('sku', 'RL-NV-5090')->first();
        $this->assertNotNull($produk->foto_produk);
        Storage::disk('public')->assertExists($produk->foto_produk);
    }

    public function test_sku_otomatis_di_generate_jika_dikosongkan(): void
    {
        $response = $this->actingAs($this->staff)->post(route('produk.store'), [
            'nama_produk' => 'Monitor Gaming 144Hz',
            'sku' => '',
            'harga' => 2_500_000,
            'jumlah_produk' => 5,
        ]);

        $response->assertRedirect(route('produk.index'));
        $produk = Produk::query()->where('nama_produk', 'Monitor Gaming 144Hz')->first();
        $this->assertNotNull($produk);
        $this->assertNotNull($produk->sku);
        $this->assertStringStartsWith('RL-PRD-', $produk->sku);
    }

    public function test_validasi_menolak_produk_tanpa_nama_dan_harga(): void
    {
        $this->actingAs($this->staff)
            ->post(route('produk.store'), ['jumlah_produk' => 1])
            ->assertSessionHasErrors(['nama_produk', 'harga']);

        $this->assertDatabaseCount('produk', 0);
    }

    public function test_sku_tidak_boleh_duplikat(): void
    {
        Produk::create(['nama_produk' => 'A', 'sku' => 'DUP', 'harga' => 1, 'jumlah_produk' => 1]);

        $this->actingAs($this->staff)
            ->post(route('produk.store'), ['nama_produk' => 'B', 'sku' => 'DUP', 'harga' => 1, 'jumlah_produk' => 1])
            ->assertSessionHasErrors('sku');
    }

    public function test_staff_bisa_mengubah_dan_menghapus_produk(): void
    {
        $produk = Produk::create(['nama_produk' => 'Lama', 'harga' => 1_000, 'jumlah_produk' => 5]);

        $this->actingAs($this->staff)->put(route('produk.update', $produk), [
            'nama_produk' => 'Baru', 'harga' => 2_000, 'jumlah_produk' => 9,
        ])->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'nama_produk' => 'Baru', 'harga' => 2_000]);

        $this->actingAs($this->staff)->delete(route('produk.destroy', $produk))
            ->assertRedirect(route('produk.index'));
        $this->assertDatabaseMissing('produk', ['id' => $produk->id]);
    }

    public function test_staff_dan_owner_bisa_unduh_template_csv(): void
    {
        $this->actingAs($this->staff)
            ->get(route('produk.template'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_staff_bisa_impor_produk_massal_via_csv(): void
    {
        $csvContent = "nama_produk,sku,kategori,harga,harga_modal,jumlah_produk,deskripsi\n".
            "Keyboard Mechanical RGB,RL-KEY-001,Aksesori,450000,350000,15,Keyboard Mechanical Switch Blue\n".
            "Mouse Gaming Wireless,RL-MOU-002,Aksesori,250000,180000,20,Mouse Optik 16000 DPI\n";

        $file = UploadedFile::fake()->createWithContent('produk.csv', $csvContent);

        $response = $this->actingAs($this->staff)->post(route('produk.import'), [
            'file_csv' => $file,
        ]);

        $response->assertRedirect(route('produk.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Keyboard Mechanical RGB',
            'sku' => 'RL-KEY-001',
            'harga' => 450000,
            'jumlah_produk' => 15,
        ]);

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Mouse Gaming Wireless',
            'sku' => 'RL-MOU-002',
            'harga' => 250000,
            'jumlah_produk' => 20,
        ]);

        $this->assertDatabaseHas('kategori_produk', [
            'nama_kategori' => 'Aksesori',
        ]);
    }

    public function test_impor_csv_dengan_format_header_berbeda_dan_pemisah_titik_koma(): void
    {
        $csvContent = "Barang;Kode Barang;Jenis;Harga Jual;HPP;QTY;Keterangan\n".
            "Headset Gaming 7.1;HS-009;Audio;Rp 350.000;Rp 250.000;12;Surround Sound\n";

        $file = UploadedFile::fake()->createWithContent('supplier.csv', $csvContent);

        $response = $this->actingAs($this->staff)->post(route('produk.import'), [
            'file_csv' => $file,
        ]);

        $response->assertRedirect(route('produk.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Headset Gaming 7.1',
            'sku' => 'HS-009',
            'harga' => 350000,
            'harga_modal' => 250000,
            'jumlah_produk' => 12,
        ]);
    }
}
