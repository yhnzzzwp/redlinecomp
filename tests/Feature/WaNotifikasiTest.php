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
            ->assertSee('https://wa.me/62812000111', false)
            ->assertSee('kirim_wa'); // centang WA semi-otomatis di form status
    }

    public function test_ganti_status_dengan_centang_wa_memicu_buka_otomatis(): void
    {
        $this->usePortal('staff');
        $servis = $this->buatServis('0812000111', StatusService::Diterima);

        $respon = $this->actingAs($servis->pegawai)->post(route('service.status', $servis), [
            'status' => StatusService::Dikerjakan->value, 'kirim_wa' => '1',
        ]);

        // Link di sesi memakai template kanonik App\Support\Wa untuk status BARU.
        $respon->assertRedirect(route('service.show', $servis));
        $link = (string) session('wa_link');
        $this->assertStringStartsWith('https://wa.me/62812000111?text=', $link);
        $this->assertSame(Wa::linkStatusServis($servis->fresh()), $link);
        $this->assertStringContainsString(rawurlencode('sedang dikerjakan'), $link);

        // Halaman tujuan membawa atribut pemicu auto-open + tombol fallback.
        $this->followingRedirects();
        $this->actingAs($servis->pegawai)
            ->post(route('service.status', $servis->fresh()), [
                'status' => StatusService::Selesai->value, 'kirim_wa' => '1',
            ])
            ->assertOk()
            ->assertSee('data-wa-auto', false)
            ->assertSee('Buka WhatsApp');
    }

    public function test_tanpa_centang_wa_tidak_ada_buka_otomatis(): void
    {
        $this->usePortal('staff');
        $servis = $this->buatServis('0812000111', StatusService::Diterima);

        $this->actingAs($servis->pegawai)->post(route('service.status', $servis), [
            'status' => StatusService::Dikerjakan->value, // kirim_wa tidak dikirim
        ])->assertSessionMissing('wa_link');
    }

    public function test_customer_tanpa_nomor_hp_tidak_pernah_memicu_wa(): void
    {
        $this->usePortal('staff');
        $servis = $this->buatServis(null, StatusService::Diterima);

        $this->actingAs($servis->pegawai)->post(route('service.status', $servis), [
            'status' => StatusService::Dikerjakan->value, 'kirim_wa' => '1',
        ])->assertSessionMissing('wa_link');

        // Form pun tidak menawarkan centang WA.
        $this->actingAs($servis->pegawai)->get(route('service.show', $servis->fresh()))
            ->assertOk()
            ->assertDontSee('kirim_wa');
    }
}
