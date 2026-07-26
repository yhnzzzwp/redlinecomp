<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class TotpTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('admin');
        $this->owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password'), 'role' => 'Owner', 'masih_bekerja' => true,
        ]);
    }

    public function test_owner_bisa_mengaktifkan_2fa_dengan_kode_valid(): void
    {
        $respon = $this->actingAs($this->owner)->get(route('keamanan'));
        $respon->assertOk()->assertSee('Aktifkan 2FA');

        $secret = (string) session('totp_setup_secret');
        $this->assertNotSame('', $secret);

        $this->actingAs($this->owner)
            ->post(route('totp.aktifkan'), ['kode' => Totp::kodeSaatIni($secret)])
            ->assertRedirect(route('keamanan'))
            ->assertSessionHas('totp_recovery_baru');

        $this->assertTrue($this->owner->fresh()->totpAktif());
        $this->assertCount(6, $this->owner->fresh()->totp_recovery);
    }

    public function test_kode_salah_tidak_mengaktifkan_2fa(): void
    {
        $this->actingAs($this->owner)->get(route('keamanan'));

        $this->actingAs($this->owner)
            ->post(route('totp.aktifkan'), ['kode' => '000000'])
            ->assertSessionHasErrors('kode');

        $this->assertFalse($this->owner->fresh()->totpAktif());
    }

    public function test_login_owner_ber2fa_ditahan_sampai_kode_benar(): void
    {
        $secret = Totp::buatSecret();
        $this->owner->forceFill(['totp_secret' => $secret])->save();

        // Password benar -> belum masuk, dialihkan ke tantangan 2FA.
        $this->post('/login', ['login' => 'owner', 'password' => 'password'])
            ->assertRedirect(route('totp.tantangan'));
        $this->assertGuest();

        // Kode salah -> tetap tamu.
        $this->from(route('totp.tantangan'))
            ->post(route('totp.verifikasi'), ['kode' => '123456'])
            ->assertSessionHasErrors('kode');
        $this->assertGuest();

        // Kode benar -> masuk ke dashboard.
        $this->post(route('totp.verifikasi'), ['kode' => Totp::kodeSaatIni($secret)])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_kode_pemulihan_hanya_bisa_dipakai_sekali(): void
    {
        $this->owner->forceFill([
            'totp_secret' => Totp::buatSecret(),
            'totp_recovery' => [Hash::make('AAAA-BBBB'), Hash::make('CCCC-DDDD')],
        ])->save();

        $this->post('/login', ['login' => 'owner', 'password' => 'password']);
        $this->post(route('totp.verifikasi'), ['kode' => 'AAAA-BBBB'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->owner);
        $this->assertCount(1, $this->owner->fresh()->totp_recovery);

        // Kode yang sama tidak berlaku lagi.
        auth()->logout();
        $this->post('/login', ['login' => 'owner', 'password' => 'password']);
        $this->post(route('totp.verifikasi'), ['kode' => 'AAAA-BBBB'])
            ->assertSessionHasErrors('kode');
        $this->assertGuest();
    }

    public function test_owner_tanpa_2fa_login_langsung(): void
    {
        $this->post('/login', ['login' => 'owner', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_nonaktifkan_2fa_butuh_password(): void
    {
        $this->owner->forceFill(['totp_secret' => Totp::buatSecret()])->save();

        $this->actingAs($this->owner)
            ->post(route('totp.nonaktifkan'), ['password' => 'salah'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($this->owner->fresh()->totpAktif());

        $this->actingAs($this->owner)
            ->post(route('totp.nonaktifkan'), ['password' => 'password'])
            ->assertRedirect(route('keamanan'));
        $this->assertFalse($this->owner->fresh()->totpAktif());
    }

    public function test_karyawan_tidak_bisa_akses_halaman_keamanan(): void
    {
        $this->usePortal('staff');
        $karyawan = Pegawai::create([
            'nama_pegawai' => 'Kar', 'username' => 'kar', 'email' => 'kar@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);

        $this->actingAs($karyawan)->get(route('keamanan'))->assertForbidden();
    }
}
