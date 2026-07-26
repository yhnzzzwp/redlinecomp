<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolePegawai;
use App\Models\Pegawai;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PegawaiCrudTest extends TestCase
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

    private function payload(array $override = []): array
    {
        return array_merge([
            'nama_pegawai' => 'Budi Santoso',
            'username' => 'budi',
            'email' => 'budi@redline.tech',
            'role' => RolePegawai::Karyawan->value,
            'password' => 'password123',
            'nomor_hp' => '081234567890',
            'alamat_pegawai' => 'Jl. Kebon Jeruk No. 10',
            'tanggal_masuk' => '2026-01-15',
            'masih_bekerja' => '1',
        ], $override);
    }

    public function test_owner_bisa_melihat_daftar_pegawai(): void
    {
        $this->actingAs($this->owner)->get(route('pegawai.index'))
            ->assertOk()
            ->assertSee('Akun Pegawai');
    }

    public function test_owner_bisa_membuat_pegawai(): void
    {
        $this->actingAs($this->owner)->post(route('pegawai.store'), $this->payload())
            ->assertRedirect(route('pegawai.index'));

        $this->assertDatabaseHas('pegawai', ['username' => 'budi', 'email' => 'budi@redline.tech']);
    }

    public function test_karyawan_tidak_boleh_akses_pegawai(): void
    {
        $this->usePortal('staff');
        $this->actingAs($this->karyawan)->get(route('pegawai.index'))->assertForbidden();
        $this->actingAs($this->karyawan)->post(route('pegawai.store'), $this->payload())->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('pegawai.index'))->assertRedirect(route('login'));
    }

    public function test_username_duplikat_ditolak(): void
    {
        Pegawai::create($this->payload(['password' => Hash::make('password123')]));

        $this->actingAs($this->owner)
            ->post(route('pegawai.store'), $this->payload())
            ->assertSessionHasErrors('username');
    }

    public function test_email_duplikat_ditolak(): void
    {
        Pegawai::create($this->payload(['password' => Hash::make('password123')]));

        $this->actingAs($this->owner)
            ->post(route('pegawai.store'), $this->payload(['username' => 'lain']))
            ->assertSessionHasErrors('email');
    }

    public function test_owner_bisa_mengubah_pegawai_tanpa_ganti_password(): void
    {
        $pegawai = Pegawai::create($this->payload(['password' => Hash::make('password123')]));
        $oldHash = $pegawai->password;

        $this->actingAs($this->owner)->put(route('pegawai.update', $pegawai), $this->payload([
            'nama_pegawai' => 'Budi Updated',
            'password' => '',
        ]))->assertRedirect(route('pegawai.index'));

        $pegawai->refresh();
        $this->assertEquals('Budi Updated', $pegawai->nama_pegawai);
        $this->assertEquals($oldHash, $pegawai->password);
    }

    public function test_owner_bisa_menghapus_pegawai_lain(): void
    {
        $pegawai = Pegawai::create($this->payload(['password' => Hash::make('password123')]));

        $this->actingAs($this->owner)->delete(route('pegawai.destroy', $pegawai))
            ->assertRedirect(route('pegawai.index'));
        $this->assertDatabaseMissing('pegawai', ['id' => $pegawai->id]);
    }

    public function test_owner_tidak_bisa_menghapus_diri_sendiri(): void
    {
        $this->actingAs($this->owner)->delete(route('pegawai.destroy', $this->owner))
            ->assertRedirect(route('pegawai.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('pegawai', ['id' => $this->owner->id]);
    }
}
