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
        $this->usePortal('staff');
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

        $this->assertMatchesRegularExpression('/^PK-\d{4}-[A-Z0-9]{6}$/', $service->nomor_resi);
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

        // Diterima → Dikerjakan (transisi valid)
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

    public function test_tambah_sparepart_mengurangi_stok_produk_otomatis(): void
    {
        $produk = \App\Models\Produk::create([
            'nama_produk' => 'yohanes',
            'sku' => 'RL-YOHANES',
            'harga' => 150_000,
            'jumlah_produk' => 15,
        ]);

        $service = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'Pelanggan X', 'nama_barang' => 'PC', 'masalah' => 'Upgrade',
        ], $this->staff);

        $this->actingAs($this->staff)->post(route('service.part', $service), [
            'produk_id' => $produk->id,
            'nama_part' => 'yohanes',
            'jumlah' => 1,
            'harga' => 150_000,
        ])->assertRedirect();

        $this->assertSame(14, $produk->fresh()->jumlah_produk);

        $part = \App\Models\PartService::where('service_id', $service->id)->first();
        $this->actingAs($this->staff)->delete(route('service.part.destroy', [$service, $part]))
            ->assertRedirect();

        $this->assertSame(15, $produk->fresh()->jumlah_produk);
    }

    public function test_tamu_tidak_bisa_akses_servis(): void
    {
        $this->get(route('service'))->assertRedirect(route('login'));
    }

    // ── Transition Tests ──────────────────────────────────────────

    public function test_menunggu_sparepart_bisa_mundur_ke_dikerjakan(): void
    {
        $svc = $this->buatDanMajuKe(StatusService::MenungguSparepart);

        $this->actingAs($this->staff)->post(route('service.status', $svc), [
            'status' => StatusService::Dikerjakan->value, 'catatan' => 'Sparepart sudah datang, lanjut kerjakan.',
        ])->assertRedirect();

        $this->assertSame(StatusService::Dikerjakan, $svc->fresh()->status);
    }

    public function test_menunggu_sparepart_bisa_lompat_ke_selesai(): void
    {
        $svc = $this->buatDanMajuKe(StatusService::MenungguSparepart);

        $this->actingAs($this->staff)->post(route('service.status', $svc), [
            'status' => StatusService::Selesai->value, 'catatan' => 'Ternyata tidak perlu part, langsung selesai.',
        ])->assertRedirect();

        $this->assertSame(StatusService::Selesai, $svc->fresh()->status);
    }

    public function test_selesai_tidak_bisa_mundur_ke_diterima(): void
    {
        $svc = $this->buatDanMajuKe(StatusService::Selesai);

        $this->actingAs($this->staff)->post(route('service.status', $svc), [
            'status' => StatusService::Diterima->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(StatusService::Selesai, $svc->fresh()->status);
    }

    public function test_selesai_tidak_bisa_mundur_ke_dikerjakan(): void
    {
        $svc = $this->buatDanMajuKe(StatusService::Selesai);

        $this->actingAs($this->staff)->post(route('service.status', $svc), [
            'status' => StatusService::Dikerjakan->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(StatusService::Selesai, $svc->fresh()->status);
    }

    public function test_sudah_diambil_tidak_bisa_diubah(): void
    {
        $svc = $this->buatDanMajuKe(StatusService::SudahDiambil);

        $this->actingAs($this->staff)->post(route('service.status', $svc), [
            'status' => StatusService::Selesai->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(StatusService::SudahDiambil, $svc->fresh()->status);
    }

    public function test_diterima_tidak_bisa_langsung_ke_selesai(): void
    {
        $svc = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'X', 'nama_barang' => 'Y', 'masalah' => 'Z',
        ], $this->staff);

        $this->actingAs($this->staff)->post(route('service.status', $svc), [
            'status' => StatusService::Selesai->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(StatusService::Diterima, $svc->fresh()->status);
    }

    /**
     * Helper: buat tiket lalu majukan ke status tertentu via alur valid.
     */
    private function buatDanMajuKe(StatusService $target): Service
    {
        $ticketService = app(ServiceTicketService::class);
        $svc = $ticketService->buat([
            'nama_customer' => 'Test', 'nama_barang' => 'Laptop', 'masalah' => 'Rusak',
        ], $this->staff);

        // Alur valid: Diterima → Dikerjakan → MenungguSparepart → Dikerjakan → Selesai → SudahDiambil
        $chain = [
            StatusService::Dikerjakan,
            StatusService::MenungguSparepart,
            StatusService::Selesai,
            StatusService::SudahDiambil,
        ];

        // Khusus untuk Selesai via MenungguSparepart, kita lompat
        $path = match ($target) {
            StatusService::Diterima => [],
            StatusService::Dikerjakan => [StatusService::Dikerjakan],
            StatusService::MenungguSparepart => [StatusService::Dikerjakan, StatusService::MenungguSparepart],
            StatusService::Selesai => [StatusService::Dikerjakan, StatusService::Selesai],
            StatusService::SudahDiambil => [StatusService::Dikerjakan, StatusService::Selesai, StatusService::SudahDiambil],
        };

        foreach ($path as $step) {
            $ticketService->updateStatus($svc, $step, null, $this->staff);
        }

        return $svc->fresh();
    }
}

