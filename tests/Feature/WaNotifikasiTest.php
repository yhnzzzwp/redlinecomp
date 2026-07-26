<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusService;
use App\Models\Pegawai;
use App\Models\Service;
use App\Support\Wa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class WaNotifikasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatServis(?string $hp, StatusService $status = StatusService::Dikerjakan): Service
    {
        $pegawai = Pegawai::query()->firstOrCreate(
            ['username' => 'kar'],
            ['nama_pegawai' => 'Karyawan', 'email' => 'kar@uji.test', 'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true],
        );

        return Service::create([
            'nomor_resi' => 'PK-2026-'.fake()->unique()->numerify('####'), 'pegawai_id' => $pegawai->id,
            'nama_customer' => 'Budi', 'nomor_hp_customer' => $hp, 'nama_barang' => 'Laptop ASUS',
            'masalah' => 'Mati total', 'biaya_service' => 150_000, 'status' => $status, 'tanggal_masuk' => now(),
        ]);
    }

    public function test_normalisasi_nomor_indonesia(): void
    {
        $this->assertSame('6285640203069', Wa::normalisasi('085640203069'));
        $this->assertSame('6285640203069', Wa::normalisasi('+62 856-4020-3069'));
        $this->assertNull(Wa::normalisasi(null));
        $this->assertNull(Wa::normalisasi('abc'));
    }

    public function test_pesan_memuat_resi_status_dan_tautan_lacak(): void
    {
        $servis = $this->buatServis('0812000111', StatusService::Selesai);
        $pesan = Wa::pesanStatus($servis);

        $this->assertStringContainsString($servis->nomor_resi, $pesan);
        $this->assertStringContainsString('SELESAI', $pesan);
        $this->assertStringContainsString('Rp 150.000', $pesan);
        $this->assertStringContainsString('/cek-servis?resi=', $pesan);
    }

    public function test_link_null_bila_customer_tanpa_nomor_hp(): void
    {
        $this->assertNull(Wa::linkStatusServis($this->buatServis(null)));
        $this->assertStringStartsWith('https://wa.me/62812000111?text=', (string) Wa::linkStatusServis($this->buatServis('0812000111')));
    }

    public function test_halaman_servis_menampilkan_tombol_wa(): void
    {
        $this->usePortal('staff');
        $servis = $this->buatServis('0812000111');

        $this->actingAs($servis->pegawai)->get(route('service.show', $servis))
            ->assertOk()
            ->assertSee('Kirim Update WA')
            ->assertSee('https://wa.me/62812000111', false);
    }
}
