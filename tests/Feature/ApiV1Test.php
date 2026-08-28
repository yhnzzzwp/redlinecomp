<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MetodeBayar;
use App\Enums\RolePegawai;
use App\Enums\StatusService;
use App\Enums\TipePromo;
use App\Enums\TransaksiStatus;
use App\Models\KategoriProduk;
use App\Models\Pegawai;
use App\Models\Perangkat;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Transaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;
    private Pegawai $karyawan;
    private string $ownerToken;
    private string $karyawanToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Pegawai::create([
            'nama_pegawai'   => 'Owner Redline',
            'username'       => 'owner',
            'email'          => 'owner@redline.test',
            'password'       => Hash::make('password123'),
            'role'           => RolePegawai::Owner,
            'masih_bekerja'  => true,
            'tanggal_masuk'  => now(),
        ]);

        $this->karyawan = Pegawai::create([
            'nama_pegawai'   => 'Karyawan Redline',
            'username'       => 'karyawan',
            'email'          => 'karyawan@redline.test',
            'password'       => Hash::make('password123'),
            'role'           => RolePegawai::Karyawan,
            'masih_bekerja'  => true,
            'tanggal_masuk'  => now(),
        ]);

        $this->ownerToken = $this->owner->createApiToken('Test Owner Device');
        $this->karyawanToken = $this->karyawan->createApiToken('Test Staff Device');
    }

    public function test_api_health(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'service' => 'Redline Backend API',
            ]);
    }

    public function test_api_login_berhasil_dan_mengembalikan_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'owner',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Login berhasil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'nama_pegawai', 'username', 'email', 'role', 'is_owner'],
                ],
            ]);

        $this->assertDatabaseHas('api_tokens', [
            'pegawai_id' => $this->owner->id,
        ]);
    }

    public function test_api_login_portal_admin_ditolak_untuk_karyawan(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'karyawan',
            'password' => 'password123',
            'portal'   => 'admin',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    public function test_api_me_dan_profile(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'username' => 'karyawan',
                    'is_owner' => false,
                ],
            ]);
    }

    public function test_api_katalog_dan_detail_produk(): void
    {
        $kategori = KategoriProduk::create(['nama_kategori' => 'Motherboard']);

        $produk = Produk::create([
            'kategori_id'      => $kategori->id,
            'sku'              => 'MB-B550',
            'nama_produk'      => 'ASUS TUF B550-PLUS',
            'deskripsi_produk' => 'Motherboard AM4',
            'show_katalog'     => true,
        ]);

        $response = $this->getJson('/api/v1/katalog');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonFragment(['nama_produk' => 'ASUS TUF B550-PLUS']);

        $detail = $this->getJson('/api/v1/katalog/' . $produk->id);
        $detail->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data'   => [
                    'produk' => [
                        'id' => $produk->id,
                        'nama_produk' => 'ASUS TUF B550-PLUS',
                    ],
                ],
            ]);
    }

    public function test_api_cek_promo_valid(): void
    {
        Promo::create([
            'nama_promo'        => 'Diskon 10 Persen',
            'kode_promo'        => 'DISKON10',
            'tipe_promo'        => TipePromo::Persen,
            'besar_promo'       => 10,
            'minimal_transaksi' => 100000,
            'maksimal_diskon'   => 50000,
            'waktu_mulai'       => now()->subDay(),
            'waktu_berakhir'    => now()->addDay(),
            'aktif'             => true,
            'kuota'             => 100,
            'terpakai'          => 0,
        ]);

        $response = $this->postJson('/api/v1/promo/cek', [
            'kode_promo' => 'DISKON10',
            'subtotal'   => 300000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Kode promo valid.',
                'data'    => [
                    'kode_promo'           => 'DISKON10',
                    'diskon'               => 30000,
                    'total_setelah_diskon' => 270000,
                ],
            ]);
    }

    public function test_api_pos_checkout_produk_dan_servis(): void
    {
        $kategori = KategoriProduk::create(['nama_kategori' => 'Aksesoris']);
        $produk = Produk::create([
            'kategori_id'  => $kategori->id,
            'sku'          => 'MOU-01',
            'nama_produk'  => 'Mouse Gaming Logitech',
            'show_katalog' => true,
        ]);

        $perangkat = Perangkat::create([
            'kode_perangkat'    => 'DEV-TEST01',
            'nama_customer'     => 'Andi',
            'nomor_hp_customer' => '081234567890',
            'merk_model'        => 'Acer Nitro 5',
        ]);

        $servis = Service::create([
            'nomor_resi'    => 'SRV-TEST01',
            'pegawai_id'    => $this->karyawan->id,
            'perangkat_id'  => $perangkat->id,
            'keluhan'       => 'Ganti Thermal Paste',
            'biaya_service' => 150000,
            'status'        => StatusService::Selesai,
            'tanggal_masuk' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson('/api/v1/pos/checkout', [
                'items' => [
                    [
                        'tipe'       => 'produk',
                        'produk_id'  => $produk->id,
                        'jumlah'     => 1,
                        'harga'      => 200000,
                    ],
                    [
                        'tipe'       => 'service',
                        'service_id' => $servis->id,
                        'jumlah'     => 1,
                        'harga'      => 150000,
                    ],
                ],
                'metode_bayar'     => MetodeBayar::Tunai->value,
                'bayar'            => 400000,
                'nama_pembeli'     => 'Andi',
                'nomor_hp_pembeli' => '081234567890',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status'  => 'success',
            ])
            ->assertJsonFragment([
                'subtotal'  => 350000,
                'total'     => 350000,
                'bayar'     => 400000,
                'kembalian' => 50000,
            ]);

        $this->assertDatabaseHas('transaksi', [
            'total' => 350000,
            'bayar' => 400000,
        ]);

        // Servis status updated to SudahDiambil
        $this->assertEquals(StatusService::SudahDiambil, $servis->fresh()->status);
    }

    public function test_api_service_ticket_create_update_status_dan_part(): void
    {
        // 1. Create service ticket via API
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson('/api/v1/admin/services', [
                'nama_customer'     => 'Budi Santoso',
                'nomor_hp_customer' => '081987654321',
                'merk_model'        => 'ASUS ROG Strix',
                'keluhan'           => 'Layar bergaris',
                'biaya_service'     => 100000,
                'estimasi_selesai'  => now()->addDays(3)->format('Y-m-d'),
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        $serviceId = $response->json('data.id');
        $this->assertNotNull($serviceId);

        // 2. Add part to service
        $partResponse = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson("/api/v1/admin/services/{$serviceId}/parts", [
                'nama_part' => 'LCD Panel 144Hz',
                'jumlah'    => 1,
                'harga'     => 850000,
            ]);

        $partResponse->assertStatus(201)
            ->assertJson([
                'status' => 'success',
            ]);

        // 3. Update status: Diterima -> Dikerjakan -> Selesai
        $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson("/api/v1/admin/services/{$serviceId}/status", [
                'status'  => StatusService::Dikerjakan->value,
                'catatan' => 'Unit mulai dibongkar dan diperiksa.',
            ])->assertStatus(200);

        $statusResponse = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->postJson("/api/v1/admin/services/{$serviceId}/status", [
                'status'  => StatusService::Selesai->value,
                'catatan' => 'LCD berhasil diganti dan ditest normal.',
            ]);

        $statusResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        // Total biaya: 100.000 + 850.000 = 950.000
        $this->assertEquals(950000, Service::find($serviceId)->totalBiaya());
    }

    public function test_api_owner_only_routes_protection(): void
    {
        // Karyawan should be forbidden from accessing owner routes
        $karyawanAccess = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->getJson('/api/v1/admin/pegawai');

        $karyawanAccess->assertStatus(403);

        // Owner should be allowed
        $ownerAccess = $this->withHeader('Authorization', 'Bearer ' . $this->ownerToken)
            ->getJson('/api/v1/admin/pegawai');

        $ownerAccess->assertStatus(200)
            ->assertJson(['status' => 'success']);
    }

    public function test_api_dashboard_summary(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->karyawanToken)
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'penjualan' => ['hari_ini', 'bulan_ini'],
                    'servis'    => ['aktif', 'siap_diambil'],
                    'ringkasan' => ['total_produk', 'promo_aktif', 'total_pegawai'],
                ],
            ]);
    }
}
