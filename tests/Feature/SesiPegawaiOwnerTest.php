<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Owner mengeluarkan seluruh perangkat pegawai LAIN dari halaman Akun Pegawai
 * (mis. HP karyawan hilang) tanpa harus menonaktifkan akunnya.
 */
final class SesiPegawaiOwnerTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;

    private Pegawai $karyawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('admin');

        $this->owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password'), 'role' => 'Owner', 'masih_bekerja' => true,
        ]);
        $this->karyawan = Pegawai::create([
            'nama_pegawai' => 'Rijal', 'username' => 'rijal', 'email' => 'rijal@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    private function buatSesi(Pegawai $pemilik, string $id, int $menitLalu = 0): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $pemilik->id,
            'ip_address' => '10.0.0.7',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/126.0 Mobile Safari/537.36',
            'payload' => base64_encode('a:0:{}'),
            'last_activity' => now()->subMinutes($menitLalu)->getTimestamp(),
        ]);
    }

    public function test_halaman_akun_pegawai_menampilkan_jumlah_perangkat_login(): void
    {
        $this->buatSesi($this->karyawan, 'sesiHp');
        $this->buatSesi($this->karyawan, 'sesiWarnet');
        $this->buatSesi($this->karyawan, 'sesiBasi', menitLalu: 120); // lifetime 30 menit — tak dihitung

        $this->actingAs($this->owner)->get(route('pegawai.index'))
            ->assertOk()
            ->assertSee('2 login')
            ->assertSee(route('pegawai.sesi.keluarkan', $this->karyawan));
    }

    public function test_owner_mengeluarkan_semua_perangkat_pegawai(): void
    {
        $this->buatSesi($this->karyawan, 'sesiHp');
        $this->buatSesi($this->karyawan, 'sesiWarnet');
        $this->buatSesi($this->owner, 'sesiOwner');
        $this->karyawan->setRememberToken('token-lama-hp-karyawan');
        $this->karyawan->save();

        $this->actingAs($this->owner)->delete(route('pegawai.sesi.keluarkan', $this->karyawan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'sesiHp']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesiWarnet']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesiOwner']); // sesi Owner tak tersentuh

        // Tanpa rotasi, cookie "Ingat perangkat" akan menghidupkan sesi lagi.
        $this->assertNotSame('token-lama-hp-karyawan', $this->karyawan->fresh()->getRememberToken());
    }

    public function test_cookie_ingat_dicabut_meski_tak_ada_sesi_aktif(): void
    {
        // Kasus HP hilang: sesi sudah kedaluwarsa, tapi recaller masih hidup.
        $this->karyawan->setRememberToken('token-lama-hp-karyawan');
        $this->karyawan->save();

        $this->actingAs($this->owner)->delete(route('pegawai.sesi.keluarkan', $this->karyawan))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotSame('token-lama-hp-karyawan', $this->karyawan->fresh()->getRememberToken());
    }

    public function test_owner_tidak_mengeluarkan_dirinya_sendiri_dari_sini(): void
    {
        $this->buatSesi($this->owner, 'sesiOwner');

        $this->actingAs($this->owner)->delete(route('pegawai.sesi.keluarkan', $this->owner))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sessions', ['id' => 'sesiOwner']);
    }

    public function test_karyawan_tidak_boleh_mengeluarkan_sesi_pegawai_lain(): void
    {
        $this->buatSesi($this->owner, 'sesiOwner');

        $this->actingAs($this->karyawan)->delete(route('pegawai.sesi.keluarkan', $this->owner))
            ->assertForbidden();

        $this->assertDatabaseHas('sessions', ['id' => 'sesiOwner']);
    }
}
