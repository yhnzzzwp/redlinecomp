<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolePegawai;
use App\Models\Pegawai;
use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pengelolaan promo lewat API — jalur yang dipakai panel admin Next.js.
 */
final class PromoApiTest extends TestCase
{
    use RefreshDatabase;

    private string $tokenOwner;
    private string $tokenKaryawan;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = Pegawai::create([
            'nama_pegawai' => 'Pemilik', 'username' => 'pemilik', 'email' => 'pemilik@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Owner,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);
        $karyawan = Pegawai::create([
            'nama_pegawai' => 'Kasir', 'username' => 'kasir', 'email' => 'kasir@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Karyawan,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);

        $this->tokenOwner = $owner->createApiToken('Uji Owner');
        $this->tokenKaryawan = $karyawan->createApiToken('Uji Karyawan');
    }

    /** @param array<string, mixed> $ganti */
    private function dataPromo(array $ganti = []): array
    {
        return array_merge([
            'nama_promo' => 'Diskon Akhir Tahun',
            'kode_promo' => 'AKHIR2026',
            'tipe_promo' => 'Persen',
            'besar_promo' => 15,
            'minimal_transaksi' => 500000,
            'maksimal_diskon' => 250000,
            'waktu_mulai' => '2026-09-01',
            'waktu_berakhir' => '2026-09-30',
            'aktif' => true,
        ], $ganti);
    }

    public function test_owner_membuat_promo(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->tokenOwner)
            ->postJson('/api/v1/admin/promos', $this->dataPromo())
            ->assertStatus(201)
            ->assertJsonPath('data.kode_promo', 'AKHIR2026');

        $this->assertDatabaseHas('promo', ['kode_promo' => 'AKHIR2026']);
    }

    public function test_promo_tanpa_tanggal_ditolak_dengan_422_bukan_500(): void
    {
        // Kolom tanggal NOT NULL di basis data. Sebelum validasinya diperketat,
        // permintaan ini lolos validasi lalu meledak di lapisan SQL sebagai 500.
        $data = $this->dataPromo();
        unset($data['waktu_mulai'], $data['waktu_berakhir']);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenOwner)
            ->postJson('/api/v1/admin/promos', $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['waktu_mulai', 'waktu_berakhir']);
    }

    public function test_persen_di_atas_seratus_ditolak(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->tokenOwner)
            ->postJson('/api/v1/admin/promos', $this->dataPromo(['besar_promo' => 1000]))
            ->assertStatus(422);
    }

    public function test_owner_mengubah_dan_mengaktifkan_promo(): void
    {
        $promo = Promo::create($this->dataPromo(['kode_promo' => 'LAMA2026', 'aktif' => false]));

        $this->withHeader('Authorization', 'Bearer '.$this->tokenOwner)
            ->putJson('/api/v1/admin/promos/'.$promo->id, $this->dataPromo([
                'kode_promo' => 'BARU2026',
                'nama_promo' => 'Nama Diubah',
            ]))
            ->assertStatus(200);

        $this->assertDatabaseHas('promo', ['id' => $promo->id, 'kode_promo' => 'BARU2026']);

        $this->withHeader('Authorization', 'Bearer '.$this->tokenOwner)
            ->postJson('/api/v1/admin/promos/'.$promo->id.'/toggle')
            ->assertStatus(200);
    }

    public function test_karyawan_tidak_boleh_mengelola_promo(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->tokenKaryawan)
            ->postJson('/api/v1/admin/promos', $this->dataPromo(['kode_promo' => 'CURI2026']))
            ->assertStatus(403);

        $this->assertDatabaseMissing('promo', ['kode_promo' => 'CURI2026']);
    }
}
