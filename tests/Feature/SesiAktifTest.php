<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Support\Perangkat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Manajemen sesi aktif: staf hanya melihat & mengeluarkan sesi MILIKNYA,
 * sesi kedaluwarsa disembunyikan, dan sesi user lain tak tersentuh.
 */
final class SesiAktifTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $staff;

    private Pegawai $lain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('staff');
        $this->staff = Pegawai::create([
            'nama_pegawai' => 'Staff Uji', 'username' => 'staff', 'email' => 'staff@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
        $this->lain = Pegawai::create([
            'nama_pegawai' => 'Lain', 'username' => 'lain', 'email' => 'lain@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    private function buatSesi(Pegawai $pemilik, string $id, int $menitLalu = 0, string $ua = 'Mozilla/5.0 (Linux; Android 14) Chrome/126.0 Mobile Safari/537.36'): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $pemilik->id,
            'ip_address' => '10.0.0.7',
            'user_agent' => $ua,
            'payload' => base64_encode('a:0:{}'),
            'last_activity' => now()->subMinutes($menitLalu)->getTimestamp(),
        ]);
    }

    public function test_halaman_menampilkan_sesi_milik_sendiri_saja(): void
    {
        $this->buatSesi($this->staff, 'sesiStaffHp');
        $this->buatSesi($this->lain, 'sesiUserLain');

        $this->actingAs($this->staff)->get(route('sesi'))
            ->assertOk()
            ->assertSee('Chrome · Android')
            ->assertSee('10.0.0.7')
            ->assertSee(route('sesi.keluarkan', 'sesiStaffHp'))
            ->assertDontSee('sesiUserLain');
    }

    public function test_sesi_kedaluwarsa_tidak_ditampilkan(): void
    {
        $this->buatSesi($this->staff, 'sesiBasi', menitLalu: 120); // lifetime 30 menit

        $this->actingAs($this->staff)->get(route('sesi'))
            ->assertOk()
            ->assertDontSee('sesiBasi');
    }

    public function test_keluarkan_perangkat_milik_sendiri(): void
    {
        $this->buatSesi($this->staff, 'sesiStaffHp');

        $this->actingAs($this->staff)->delete(route('sesi.keluarkan', 'sesiStaffHp'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'sesiStaffHp']);
    }

    public function test_sesi_user_lain_tidak_bisa_dikeluarkan(): void
    {
        $this->buatSesi($this->lain, 'sesiUserLain');

        $this->actingAs($this->staff)->delete(route('sesi.keluarkan', 'sesiUserLain'))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('sessions', ['id' => 'sesiUserLain']);
    }

    public function test_keluarkan_semua_perangkat_lain(): void
    {
        $this->buatSesi($this->staff, 'sesiHp');
        $this->buatSesi($this->staff, 'sesiWarnet');
        $this->buatSesi($this->lain, 'sesiUserLain');

        $this->actingAs($this->staff)->post(route('sesi.keluarkan-lain'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'sesiHp']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesiWarnet']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesiUserLain']); // milik orang lain utuh
    }

    public function test_halaman_butuh_login(): void
    {
        $this->get(route('sesi'))->assertRedirect(route('login'));
    }

    public function test_mengeluarkan_perangkat_merotasi_remember_token(): void
    {
        // Tanpa rotasi, perangkat ber-cookie "Ingat perangkat" akan membuat
        // sesi baru pada request berikutnya — seolah tak pernah dikeluarkan.
        $this->staff->setRememberToken('token-lama-perangkat');
        $this->staff->save();
        $this->buatSesi($this->staff, 'sesiHp');

        $this->actingAs($this->staff)->post(route('sesi.keluarkan-lain'))->assertRedirect();

        $this->assertNotSame('token-lama-perangkat', $this->staff->fresh()->getRememberToken());
    }

    public function test_remember_token_tidak_dirotasi_bila_tak_ada_yang_dikeluarkan(): void
    {
        $this->staff->setRememberToken('token-lama-perangkat');
        $this->staff->save();

        $this->actingAs($this->staff)->post(route('sesi.keluarkan-lain'))->assertRedirect();

        $this->assertSame('token-lama-perangkat', $this->staff->fresh()->getRememberToken());
    }

    public function test_pegawai_nonaktif_langsung_ter_logout(): void
    {
        $this->actingAs($this->staff)->get(route('sesi'))->assertOk();

        $this->staff->update(['masih_bekerja' => false]);

        $this->actingAs($this->staff)->get(route('sesi'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_label_perangkat_dari_user_agent(): void
    {
        $this->assertSame('Chrome · Android', Perangkat::label('Mozilla/5.0 (Linux; Android 14) Chrome/126.0 Mobile Safari/537.36'));
        $this->assertSame('Safari · iOS', Perangkat::label('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5) Version/17.5 Mobile/15E148 Safari/604.1'));
        $this->assertSame('Edge · Windows', Perangkat::label('Mozilla/5.0 (Windows NT 10.0; Win64) Chrome/126.0 Safari/537.36 Edg/126.0'));
        $this->assertSame('Firefox · Linux', Perangkat::label('Mozilla/5.0 (X11; Linux x86_64; rv:127.0) Gecko/20100101 Firefox/127.0'));
        $this->assertSame('Perangkat tak dikenal', Perangkat::label(null));
    }
}
