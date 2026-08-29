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
 * Pelacakan resi oleh pelanggan.
 *
 * Dua kegagalan nyata yang dikunci di sini:
 *
 *  1. Frontend Next.js mengirim ?nomor_resi=, backend membaca ?resi=. Akibatnya
 *     pelacakan dari situs SELALU 422 walau resinya benar.
 *  2. Resi dicetak "PK-2026-0001" tetapi diketik ulang pelanggan sebagai
 *     "PK 2026 00 01". Pencocokan persis menjawab "tidak ditemukan", yang
 *     terbaca sebagai data hilang padahal hanya soal pemisah.
 */
final class CekResiTest extends TestCase
{
    use RefreshDatabase;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $pegawai = Pegawai::create([
            'nama_pegawai' => 'Kasir', 'username' => 'kasir', 'email' => 'kasir@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Karyawan,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);

        $perangkat = Perangkat::create([
            'kode_perangkat' => 'DEV-UJI-0001',
            'nama_customer' => 'Budi Santoso',
            'nomor_hp_customer' => '081234567890',
            'merk_model' => 'Asus ROG',
        ]);

        $this->service = Service::create([
            'nomor_resi' => 'PK-2026-0001',
            'perangkat_id' => $perangkat->id,
            'pegawai_id' => $pegawai->id,
            'keluhan' => 'Mati total',
            'biaya_service' => 100_000,
            'status' => StatusService::Diterima,
            'tanggal_masuk' => now(),
        ]);
    }

    public function test_resi_persis_ditemukan(): void
    {
        $this->getJson('/api/v1/service/cek?resi=PK-2026-0001')
            ->assertStatus(200)
            ->assertJsonPath('data.nomor_resi', 'PK-2026-0001');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function variasiKetikan(): array
    {
        return [
            'spasi sebagai pemisah' => ['PK 2026 00 01'],
            'tanpa pemisah' => ['PK20260001'],
            'huruf kecil' => ['pk-2026-0001'],
            'garis bawah' => ['PK_2026_0001'],
            'berspasi di ujung' => ['  PK-2026-0001  '],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('variasiKetikan')]
    public function test_resi_yang_diketik_ulang_tetap_ditemukan(string $ketikan): void
    {
        $this->getJson('/api/v1/service/cek?resi='.urlencode($ketikan))
            ->assertStatus(200)
            ->assertJsonPath('data.nomor_resi', 'PK-2026-0001');
    }

    public function test_parameter_nomor_resi_dari_frontend_juga_diterima(): void
    {
        $this->getJson('/api/v1/service/cek?nomor_resi=PK-2026-0001')
            ->assertStatus(200)
            ->assertJsonPath('data.nomor_resi', 'PK-2026-0001');
    }

    public function test_resi_asing_tetap_404(): void
    {
        $this->getJson('/api/v1/service/cek?resi=PK-2026-9999')
            ->assertStatus(404);
    }

    public function test_resi_kosong_tetap_422(): void
    {
        $this->getJson('/api/v1/service/cek?resi=')
            ->assertStatus(422);
    }

    public function test_portal_publik_blade_juga_menerima_ketikan_berantakan(): void
    {
        $this->get('/cek-servis?resi='.urlencode('pk 2026 00 01'))
            ->assertStatus(200)
            ->assertSee('PK-2026-0001');
    }
}
