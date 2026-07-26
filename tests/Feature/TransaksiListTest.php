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
        $this->usePortal('staff');
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

    public function test_struk_thermal_bisa_dibuka_kasir(): void
    {
        $trx = \App\Models\Transaksi::create([
            'kode_nota' => 'INV-STRUK', 'pegawai_id' => $this->karyawan->id,
            'subtotal' => 150_000, 'total' => 150_000, 'bayar' => 200_000, 'kembalian' => 50_000,
        ]);

        $this->actingAs($this->karyawan)->get(route('pos.struk', $trx))
            ->assertOk()
            ->assertSee('INV-STRUK')
            ->assertSee('Cetak Struk')
            ->assertSee('Rp 150.000');

        auth()->logout();
        $this->get(route('pos.struk', $trx))->assertRedirect(route('login'));
    }
}
