<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Service;
use App\Models\Transaksi;
use App\Enums\StatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_katalog_menampilkan_produk_yang_diizinkan(): void
    {
        $kategori = KategoriProduk::create(['nama_kategori' => 'CPU']);
        Produk::create(['kategori_id' => $kategori->id, 'nama_produk' => 'Tampil', 'sku' => 'SKU-1', 'harga' => 1000, 'jumlah_produk' => 10, 'show_katalog' => true]);
        Produk::create(['kategori_id' => $kategori->id, 'nama_produk' => 'Sembunyi', 'sku' => 'SKU-2', 'harga' => 1000, 'jumlah_produk' => 10, 'show_katalog' => false]);

        $this->get(route('catalogue'))
            ->assertOk()
            ->assertSee('Tampil')
            ->assertDontSee('Sembunyi');
    }

    public function test_detail_katalog_bisa_diakses_jika_diizinkan(): void
    {
        $kategori = KategoriProduk::create(['nama_kategori' => 'CPU']);
        $p1 = Produk::create(['kategori_id' => $kategori->id, 'nama_produk' => 'Tampil', 'sku' => 'SKU-1', 'harga' => 1000, 'jumlah_produk' => 10, 'show_katalog' => true]);
        $p2 = Produk::create(['kategori_id' => $kategori->id, 'nama_produk' => 'Sembunyi', 'sku' => 'SKU-2', 'harga' => 1000, 'jumlah_produk' => 10, 'show_katalog' => false]);

        $this->get(route('catalogue.show', $p1))->assertOk()->assertSee('Tampil');
        $this->get(route('catalogue.show', $p2))->assertNotFound();
    }

    public function test_cek_servis_menampilkan_status_jika_ada(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'nama_pegawai' => 'Test', 'username' => 'test', 'email' => 'test@uji.test', 'password' => 'pass', 'role' => 'Karyawan', 'masih_bekerja' => true
        ]);
        $service = Service::create([
            'nomor_resi' => 'PK-1234-5678', 'pegawai_id' => $pegawai->id, 'nama_customer' => 'Budi', 'nama_barang' => 'Laptop',
            'masalah' => 'Rusak', 'biaya_service' => 0, 'status' => StatusService::Diterima, 'tanggal_masuk' => now()
        ]);

        $this->get(route('cek.servis', ['resi' => 'PK-1234-5678']))
            ->assertOk()
            ->assertSee('Laptop')
            ->assertSee('Diterima');

        $this->get(route('cek.servis', ['resi' => 'SALAH']))
            ->assertOk()
            ->assertSee('Nomor resi tidak ditemukan');
    }

    public function test_cek_nota_menampilkan_detail_jika_ada(): void
    {
        $pegawai = \App\Models\Pegawai::create([
            'nama_pegawai' => 'Test', 'username' => 'test2', 'email' => 'test2@uji.test', 'password' => 'pass', 'role' => 'Karyawan', 'masih_bekerja' => true
        ]);
        $transaksi = Transaksi::create([
            'kode_nota' => 'INV-001', 'pegawai_id' => $pegawai->id, 'subtotal' => 100, 'total' => 100, 'bayar' => 100, 'kembalian' => 0
        ]);

        $this->get(route('cek.nota', ['nota' => 'INV-001']))
            ->assertOk()
            ->assertSee('INV-001');

        $this->get(route('cek.nota', ['nota' => 'SALAH']))
            ->assertOk()
            ->assertSee('Kode nota tidak ditemukan');
    }

    public function test_halaman_about_bisa_diakses(): void
    {
        $this->get(route('about'))->assertOk()->assertSee('Tentang Kami');
    }
}
