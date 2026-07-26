<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AnalyticsTest extends TestCase
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
            'nama_pegawai' => 'Karyawan', 'username' => 'kar', 'email' => 'kar@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_owner_bisa_akses_analytics(): void
    {
        $this->actingAs($this->owner)->get(route('analytics'))
            ->assertOk()
            ->assertSee('Analisis Penjualan');
    }

    public function test_owner_bisa_cetak_laporan(): void
    {
        $this->actingAs($this->owner)->get(route('analytics.cetak'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_karyawan_tidak_bisa_akses_analytics(): void
    {
        $this->usePortal('staff');
        $this->actingAs($this->karyawan)->get(route('analytics'))->assertForbidden();
        $this->actingAs($this->karyawan)->get(route('analytics.cetak'))->assertForbidden();
    }
}
