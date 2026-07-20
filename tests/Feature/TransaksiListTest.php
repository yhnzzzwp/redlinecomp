<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class TransaksiListTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $karyawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->karyawan = Pegawai::create([
            'nama_pegawai' => 'Karyawan', 'username' => 'kar', 'email' => 'kar@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_karyawan_bisa_melihat_daftar_transaksi(): void
    {
        $this->actingAs($this->karyawan)->get(route('transaksi.index'))
            ->assertOk()
            ->assertSee('Daftar Transaksi');
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('transaksi.index'))->assertRedirect(route('login'));
    }
}
