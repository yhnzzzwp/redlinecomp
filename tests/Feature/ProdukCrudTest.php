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
}
