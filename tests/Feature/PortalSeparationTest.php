<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pemisahan zona per subdomain: publik (host utama), karyawan.*, admin.*.
 * Login Owner hanya di portal admin, Karyawan hanya di portal karyawan.
 */
final class PortalSeparationTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;

    private Pegawai $karyawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password'), 'role' => 'Owner', 'masih_bekerja' => true,
        ]);
        $this->karyawan = Pegawai::create([
            'nama_pegawai' => 'Karyawan', 'username' => 'kar', 'email' => 'kar@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_zona_internal_tersembunyi_dari_host_publik(): void
    {
        $this->usePortal('public');

        $this->get(route('login'))->assertNotFound();
        $this->get(route('dashboard'))->assertNotFound();
        $this->get(route('pos'))->assertNotFound();
    }

    public function test_halaman_publik_di_host_portal_dialihkan_ke_login(): void
    {
        $this->usePortal('admin');
        $this->get(route('landing'))->assertRedirect(route('login'));

        $this->usePortal('staff');
        $this->get(route('catalogue'))->assertRedirect(route('login'));
    }

    public function test_owner_hanya_bisa_login_di_portal_admin(): void
    {
        $this->usePortal('admin');
        $this->post('/login', ['login' => 'owner', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->owner);

        auth()->logout();

        $this->usePortal('staff');
        $this->from(route('login'))
            ->post('/login', ['login' => 'owner', 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_karyawan_hanya_bisa_login_di_portal_karyawan(): void
    {
        $this->usePortal('staff');
        $this->post('/login', ['login' => 'kar', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->karyawan);

        auth()->logout();

        $this->usePortal('admin');
        $this->from(route('login'))
            ->post('/login', ['login' => 'kar', 'password' => 'password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_sesi_role_salah_portal_ditolak(): void
    {
        $this->usePortal('staff');
        $this->actingAs($this->owner)->get(route('dashboard'))->assertForbidden();

        $this->usePortal('admin');
        $this->actingAs($this->karyawan)->get(route('dashboard'))->assertForbidden();
    }

    public function test_host_portal_tidak_diindeks_mesin_pencari(): void
    {
        $this->usePortal('admin');
        $this->get(route('login'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->usePortal('public');
        $this->get(route('landing'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }
}
