<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PWA untuk POS: manifest hanya tersedia di portal internal (karyawan/admin),
 * 404 dari host publik, ikon yang dirujuk benar-benar ada, dan layout
 * internal memuat tag pemasangan (manifest + apple-touch-icon).
 */
final class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_tersedia_di_portal_karyawan(): void
    {
        $this->usePortal('staff');

        $respon = $this->get(route('pwa.manifest'));

        $respon->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJson([
                'name' => 'SIRC POS · Portal Karyawan',
                'short_name' => 'SIRC POS',
                'start_url' => '/pos',
                'display' => 'standalone',
            ]);
    }

    public function test_manifest_portal_admin_memakai_label_admin(): void
    {
        $this->usePortal('admin');

        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertJson(['name' => 'SIRC POS · Admin Console']);
    }

    public function test_manifest_tersembunyi_dari_host_publik(): void
    {
        $this->usePortal('public');

        $this->get(route('pwa.manifest'))->assertNotFound();
    }

    public function test_seluruh_ikon_manifest_benar_benar_ada(): void
    {
        $this->usePortal('staff');

        $ikon = $this->get(route('pwa.manifest'))->json('icons');

        $this->assertNotEmpty($ikon);
        foreach ($ikon as $entri) {
            $this->assertFileExists(public_path($entri['src']), "Ikon {$entri['src']} tidak ditemukan");
        }
        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
    }

    public function test_layout_internal_memuat_tag_pemasangan_pwa(): void
    {
        $this->usePortal('staff');
        $karyawan = Pegawai::create([
            'nama_pegawai' => 'Karyawan', 'username' => 'kar', 'email' => 'kar@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);

        $this->actingAs($karyawan)
            ->get(route('pos'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(route('pwa.manifest'))
            ->assertSee('apple-touch-icon');
    }

    public function test_halaman_publik_tanpa_tag_pwa(): void
    {
        $this->usePortal('public');

        $this->get(route('landing'))
            ->assertOk()
            ->assertDontSee('manifest.webmanifest');
    }
}
