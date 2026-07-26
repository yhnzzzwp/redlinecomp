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
            $jalur = public_path($entri['src']);
            $this->assertFileExists($jalur, "Ikon {$entri['src']} tidak ditemukan");

            // Dimensi file nyata harus cocok dengan deklarasi `sizes` di manifest
            // (menjaga regenerasi via scripts/buat-ikon-pwa.php tetap benar).
            [$lebar, $tinggi] = getimagesize($jalur) ?: [0, 0];
            $this->assertSame($entri['sizes'], "{$lebar}x{$tinggi}", "Dimensi {$entri['src']} tidak cocok");
        }

        [$lebar, $tinggi] = getimagesize(public_path('icons/apple-touch-icon.png')) ?: [0, 0];
        $this->assertSame('180x180', "{$lebar}x{$tinggi}");
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

    public function test_halaman_login_memuat_link_manifest(): void
    {
        $this->usePortal('staff');

        // Kasir bisa memasang POS dari halaman pertama yang ia lihat di perangkat baru.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(route('pwa.manifest'));
    }

    public function test_service_worker_dan_halaman_offline_tersedia(): void
    {
        // File statis dilayani webserver — pastikan ada dan isinya benar.
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('offline.html'));

        $sw = (string) file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('/offline.html', $sw);
        foreach (['/build/', '/fonts/', '/icons/'] as $prefix) {
            $this->assertStringContainsString($prefix, $sw, "sw.js tidak meng-cache {$prefix}");
        }
        // Jaga keputusan desain: HTML/data tidak di-cache — navigasi via jaringan.
        $this->assertStringContainsString("req.mode === 'navigate'", $sw);

        // Halaman offline mandiri: tanpa <script>, tanpa aset eksternal.
        $offline = (string) file_get_contents(public_path('offline.html'));
        $this->assertStringNotContainsString('<script', $offline);
        $this->assertStringNotContainsString('http://', $offline);
        $this->assertStringNotContainsString('https://', $offline);
    }

    public function test_csp_internal_mengizinkan_worker_self(): void
    {
        $this->usePortal('staff');

        $csp = (string) $this->get(route('login'))->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("worker-src 'self'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', explode('worker-src', $csp)[1] ?? '');
    }

    public function test_halaman_publik_tanpa_tag_pwa(): void
    {
        $this->usePortal('public');

        $this->get(route('landing'))
            ->assertOk()
            ->assertDontSee('manifest.webmanifest');
    }
}
