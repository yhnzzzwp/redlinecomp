<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolePegawai;
use App\Enums\StatusService;
use App\Enums\TransaksiStatus;
use App\Models\Pegawai;
use App\Models\Perangkat;
use App\Models\Service;
use App\Models\Transaksi;
use App\Services\KodeGenerator;
use App\Support\Privasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Dua hal: penyamaran PII pada permukaan publik, dan pengelolaan sesi
 * (token API) milik sendiri.
 */
final class PrivasiDanSesiTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $karyawan;
    private Pegawai $lain;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->karyawan = Pegawai::create([
            'nama_pegawai' => 'Kasir Satu', 'username' => 'kasir1', 'email' => 'kasir1@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Karyawan,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);

        $this->lain = Pegawai::create([
            'nama_pegawai' => 'Kasir Dua', 'username' => 'kasir2', 'email' => 'kasir2@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Karyawan,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);

        $this->token = $this->karyawan->createApiToken('Perangkat Uji');
    }

    private function bearer(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ─── Penyamaran PII di permukaan publik ────────────────────────

    public function test_helper_privasi_menyamarkan_nama_dan_nomor(): void
    {
        $this->assertSame('Budi S.', Privasi::namaSingkat('Budi Santoso'));
        $this->assertSame('Andi', Privasi::namaSingkat('Andi'));
        $this->assertSame('****7890', Privasi::nomorHp('081234567890'));
        $this->assertNull(Privasi::namaSingkat(null));
        $this->assertNull(Privasi::nomorHp(''));
    }

    public function test_endpoint_perangkat_publik_tidak_membocorkan_identitas_utuh(): void
    {
        $perangkat = Perangkat::create([
            'kode_perangkat'    => 'PK-QR-001',
            'nama_customer'     => 'Budi Santoso',
            'nomor_hp_customer' => '081234567890',
            'merk_model'        => 'Asus ROG',
        ]);

        $res = $this->getJson('/api/v1/perangkat/' . $perangkat->kode_perangkat)
            ->assertStatus(200);

        $res->assertJsonPath('data.nama_customer', 'Budi S.');
        $res->assertJsonPath('data.nomor_hp_customer', '****7890');

        // Nilai utuh tidak boleh muncul di mana pun dalam respons.
        $isi = $res->getContent();
        $this->assertStringNotContainsString('Budi Santoso', (string) $isi);
        $this->assertStringNotContainsString('081234567890', (string) $isi);
    }

    public function test_cek_servis_publik_tidak_membocorkan_identitas_utuh(): void
    {
        $perangkat = Perangkat::create([
            'kode_perangkat'    => 'PK-QR-002',
            'nama_customer'     => 'Siti Aminah',
            'nomor_hp_customer' => '081200009999',
            'merk_model'        => 'Lenovo',
        ]);

        $service = Service::create([
            'nomor_resi'    => (new KodeGenerator())->resi(),
            'perangkat_id'  => $perangkat->id,
            'pegawai_id'    => $this->karyawan->id,
            'keluhan'       => 'Mati total',
            'biaya_service' => 100_000,
            'status'        => StatusService::Diterima,
            'tanggal_masuk' => now(),
        ]);

        $isi = (string) $this->getJson('/api/v1/service/cek?resi=' . $service->nomor_resi)
            ->assertStatus(200)
            ->getContent();

        $this->assertStringNotContainsString('Siti Aminah', $isi);
        $this->assertStringNotContainsString('081200009999', $isi);
        $this->assertStringContainsString('Siti A.', $isi);
    }

    public function test_nota_publik_menyamarkan_pembeli_dan_tidak_mengirim_nomor_hp(): void
    {
        Transaksi::create([
            'kode_nota' => '654321', 'pegawai_id' => $this->karyawan->id,
            'metode_bayar' => 'Tunai', 'subtotal' => 50_000, 'diskon' => 0,
            'total' => 50_000, 'bayar' => 50_000, 'kembalian' => 0,
            'nama_pembeli' => 'Andi Wijaya', 'nomor_hp_pembeli' => '081277778888',
            'status' => TransaksiStatus::Normal,
        ]);

        $res = $this->getJson('/api/v1/nota/654321')->assertStatus(200);

        $res->assertJsonPath('data.nama_pembeli', 'Andi W.');
        $res->assertJsonMissingPath('data.nomor_hp_pembeli');

        $isi = (string) $res->getContent();
        $this->assertStringNotContainsString('Andi Wijaya', $isi);
        $this->assertStringNotContainsString('081277778888', $isi);
    }

    // ─── Sesi (token API) ──────────────────────────────────────────

    public function test_daftar_sesi_hanya_milik_sendiri(): void
    {
        $this->lain->createApiToken('Perangkat Orang Lain');

        $res = $this->withHeaders($this->bearer())
            ->getJson('/api/v1/auth/sesi')
            ->assertStatus(200);

        $data = $res->json('data');
        $this->assertCount(1, $data, 'Hanya token milik sendiri yang boleh tampil.');
        $this->assertTrue($data[0]['perangkat_ini']);
    }

    public function test_tidak_bisa_mengeluarkan_token_pegawai_lain(): void
    {
        $this->lain->createApiToken('Perangkat Orang Lain');
        $idOrangLain = $this->lain->apiTokens()->firstOrFail()->id;

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/auth/sesi/' . $idOrangLain)
            ->assertStatus(404);

        $this->assertDatabaseHas('api_tokens', ['id' => $idOrangLain]);
    }

    public function test_tidak_bisa_mengeluarkan_perangkat_sendiri_yang_sedang_dipakai(): void
    {
        $idSaya = $this->karyawan->apiTokens()->firstOrFail()->id;

        $this->withHeaders($this->bearer())
            ->deleteJson('/api/v1/auth/sesi/' . $idSaya)
            ->assertStatus(422);

        $this->assertDatabaseHas('api_tokens', ['id' => $idSaya]);
    }

    public function test_keluarkan_lain_menyisakan_perangkat_ini(): void
    {
        $this->karyawan->createApiToken('HP Lama');
        $this->karyawan->createApiToken('Laptop Warnet');
        $this->assertSame(3, $this->karyawan->apiTokens()->count());

        $this->withHeaders($this->bearer())
            ->postJson('/api/v1/auth/sesi/keluarkan-lain')
            ->assertStatus(200)
            ->assertJsonPath('data.dikeluarkan', 2);

        $this->assertSame(1, $this->karyawan->apiTokens()->count());
    }
}
