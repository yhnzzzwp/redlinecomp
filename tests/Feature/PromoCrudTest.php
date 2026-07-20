<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TipePromo;
use App\Models\Pegawai;
use App\Models\Promo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class PromoCrudTest extends TestCase
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

    private function payload(array $override = []): array
    {
        return array_merge([
            'nama_promo' => 'Flash Sale', 'kode_promo' => 'gaming40', 'tipe_promo' => TipePromo::Persen->value,
            'besar_promo' => 40, 'minimal_transaksi' => 5_000_000, 'maksimal_diskon' => 2_000_000,
            'waktu_mulai' => '2026-07-01', 'waktu_berakhir' => '2026-12-31', 'aktif' => '1',
        ], $override);
    }

    public function test_owner_bisa_membuat_promo_dan_kode_jadi_kapital(): void
    {
        $this->actingAs($this->owner)->post(route('promo.store'), $this->payload())
            ->assertRedirect(route('promo.index'));

        $this->assertDatabaseHas('promo', ['kode_promo' => 'GAMING40', 'besar_promo' => 40]);
    }

    public function test_karyawan_tidak_boleh_akses_promo(): void
    {
        $this->actingAs($this->karyawan)->get(route('promo.index'))->assertForbidden();
        $this->actingAs($this->karyawan)->post(route('promo.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('promo', 0);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('promo.index'))->assertRedirect(route('login'));
    }

    public function test_persen_di_atas_100_ditolak(): void
    {
        $this->actingAs($this->owner)
            ->post(route('promo.store'), $this->payload(['besar_promo' => 150]))
            ->assertSessionHasErrors('besar_promo');
    }

    public function test_kode_duplikat_ditolak(): void
    {
        Promo::create($this->payload(['kode_promo' => 'DUP', 'tipe_promo' => TipePromo::Persen]));

        $this->actingAs($this->owner)
            ->post(route('promo.store'), $this->payload(['kode_promo' => 'dup']))
            ->assertSessionHasErrors('kode_promo');
    }

    public function test_owner_bisa_mengubah_dan_menghapus(): void
    {
        $promo = Promo::create($this->payload(['kode_promo' => 'EDIT', 'tipe_promo' => TipePromo::Persen]));

        $this->actingAs($this->owner)->put(route('promo.update', $promo), $this->payload([
            'kode_promo' => 'EDIT', 'besar_promo' => 25,
        ]))->assertRedirect(route('promo.index'));
        $this->assertDatabaseHas('promo', ['id' => $promo->id, 'besar_promo' => 25]);

        $this->actingAs($this->owner)->delete(route('promo.destroy', $promo))
            ->assertRedirect(route('promo.index'));
        $this->assertDatabaseMissing('promo', ['id' => $promo->id]);
    }
}
