<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolePegawai;
use App\Enums\StatusService;
use App\Models\Pegawai;
use App\Models\Perangkat;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pencarian unit lewat kode pada stiker QR.
 *
 * Stiker QR menempel di badan laptop pelanggan dan memuat URL ke halaman unit
 * di panel staf. Jalur ini yang dipakai pemindai kamera, dan sengaja berbeda
 * dari endpoint publik /api/v1/perangkat/{kode}: yang publik menyamarkan nama
 * dan nomor pelanggan, sedangkan staf yang sedang memegang unitnya butuh data
 * apa adanya.
 */
final class UnitQrTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private Perangkat $perangkat;

    protected function setUp(): void
    {
        parent::setUp();

        $karyawan = Pegawai::create([
            'nama_pegawai' => 'Kasir Satu', 'username' => 'kasir1', 'email' => 'kasir1@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Karyawan,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);
        $this->token = $karyawan->createApiToken('Uji Pemindai');

        $this->perangkat = Perangkat::create([
            'kode_perangkat' => 'DEV-ABC12345',
            'nama_customer' => 'Budi Santoso',
            'nomor_hp_customer' => '081234567890',
            'merk_model' => 'Asus TUF A15',
            'serial_number' => 'SN-001',
        ]);

        Service::create([
            'nomor_resi' => 'PK-2026-0001',
            'perangkat_id' => $this->perangkat->id,
            'pegawai_id' => $karyawan->id,
            'keluhan' => 'Mati total',
            'biaya_service' => 150_000,
            'status' => StatusService::Selesai,
            'tanggal_masuk' => now()->subDays(3),
        ]);
    }

    public function test_staf_membuka_riwayat_unit_dari_kode_stiker(): void
    {
        $res = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/admin/perangkat/kode/DEV-ABC12345')
            ->assertStatus(200);

        // Tanpa penyamaran: yang membaca adalah staf, bukan pengunjung situs.
        $res->assertJsonPath('data.nama_customer', 'Budi Santoso')
            ->assertJsonPath('data.nomor_hp_customer', '081234567890')
            ->assertJsonPath('data.merk_model', 'Asus TUF A15')
            ->assertJsonPath('data.services.0.nomor_resi', 'PK-2026-0001')
            ->assertJsonPath('data.services.0.keluhan', 'Mati total')
            ->assertJsonPath('data.services.0.total_biaya', 150_000);
    }

    public function test_kode_tak_dikenal_menjawab_404(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/admin/perangkat/kode/DEV-TIDAKADA')
            ->assertStatus(404);
    }

    public function test_tanpa_login_tidak_bisa_membaca_riwayat_unit(): void
    {
        $this->getJson('/api/v1/admin/perangkat/kode/DEV-ABC12345')
            ->assertStatus(401);
    }

    public function test_rute_kode_tidak_menelan_rute_id(): void
    {
        // '/admin/perangkat/kode/...' didaftarkan sebelum '/admin/perangkat/{id}';
        // pencarian berdasarkan id harus tetap bekerja seperti semula.
        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->getJson('/api/v1/admin/perangkat/'.$this->perangkat->id)
            ->assertStatus(200)
            ->assertJsonPath('data.kode_perangkat', 'DEV-ABC12345');
    }

    public function test_endpoint_publik_tetap_menyamarkan_identitas(): void
    {
        $this->getJson('/api/v1/perangkat/DEV-ABC12345')
            ->assertStatus(200)
            ->assertJsonPath('data.nama_customer', 'Budi S.')
            ->assertJsonPath('data.nomor_hp_customer', '****7890');
    }
}
