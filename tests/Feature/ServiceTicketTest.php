<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\StatusService;
use App\Models\Pegawai;
use App\Models\Service;
use App\Services\ServiceTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ServiceTicketTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->staff = Pegawai::create([
            'nama_pegawai' => 'Teknisi Uji', 'username' => 'teknisi', 'email' => 'teknisi@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_membuat_tiket_menghasilkan_resi_dan_riwayat_awal(): void
    {
        $service = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'Budi', 'nama_barang' => 'Laptop Asus', 'masalah' => 'Mati total',
        ], $this->staff);

        $this->assertMatchesRegularExpression('/^PK-\d{4}-\d{4}$/', $service->nomor_resi);
        $this->assertSame(StatusService::Diterima, $service->status);
        $this->assertDatabaseCount('service_status', 1);
        $this->assertDatabaseHas('service_status', [
            'service_id' => $service->id, 'status' => StatusService::Diterima->value,
        ]);
    }

    public function test_update_status_menambah_riwayat_tanpa_menimpa(): void
    {
        $service = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'Budi', 'nama_barang' => 'Laptop', 'masalah' => 'Rusak',
        ], $this->staff);

        $this->actingAs($this->staff)->post(route('service.status', $service), [
            'status' => StatusService::Dikerjakan->value, 'catatan' => 'Mulai diperbaiki.',
        ])->assertRedirect(route('service.show', $service));

        $this->assertSame(StatusService::Dikerjakan, $service->fresh()->status);
        $this->assertDatabaseCount('service_status', 2);
    }

    public function test_validasi_store_menolak_data_kosong(): void
    {
        $this->actingAs($this->staff)->post(route('service.store'), [])
            ->assertSessionHasErrors(['nama_customer', 'nama_barang', 'masalah']);
        $this->assertDatabaseCount('service', 0);
    }

    public function test_tambah_sparepart_menghitung_subtotal(): void
    {
        $service = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'A', 'nama_barang' => 'B', 'masalah' => 'C',
        ], $this->staff);

        $this->actingAs($this->staff)->post(route('service.part', $service), [
            'nama_part' => 'Thermal Paste', 'jumlah' => 2, 'harga' => 75_000,
        ])->assertRedirect();

        $this->assertDatabaseHas('part_service', [
            'service_id' => $service->id, 'nama_part' => 'Thermal Paste', 'subtotal' => 150_000,
        ]);
    }

    public function test_tamu_tidak_bisa_akses_servis(): void
    {
        $this->get(route('service'))->assertRedirect(route('login'));
    }
}
