<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RolePegawai;
use App\Enums\StatusService;
use App\Enums\TransaksiStatus;
use App\Models\PartService;
use App\Models\Pegawai;
use App\Models\Perangkat;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Transaksi;
use App\Services\KodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Integritas uang pada jalur POS.
 *
 * Catatan: PosCheckoutTest / PosDaftarServisTest / ServiceTicketTest yang lama
 * semuanya sudah gagal sebelum perubahan ini akibat drift skema (kolom
 * perangkat_id, harga/jumlah_produk). Berkas ini ditulis memakai bentuk skema
 * yang berlaku sekarang supaya perbaikan integritas uang benar-benar terbukti.
 */
final class IntegritasKeuanganTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $owner;
    private Pegawai $karyawan;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = Pegawai::create([
            'nama_pegawai' => 'Owner', 'username' => 'owner', 'email' => 'owner@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Owner,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);

        $this->karyawan = Pegawai::create([
            'nama_pegawai' => 'Kasir', 'username' => 'kasir', 'email' => 'kasir@uji.test',
            'password' => Hash::make('password123'), 'role' => RolePegawai::Karyawan,
            'masih_bekerja' => true, 'tanggal_masuk' => now(),
        ]);

        $this->token = $this->karyawan->createApiToken('Perangkat Uji');
    }

    /** Servis Rp 500.000 jasa + Rp 300.000 part = Rp 800.000 menurut server. */
    private function buatServis(StatusService $status = StatusService::Selesai): Service
    {
        $perangkat = Perangkat::create([
            'kode_perangkat' => 'PK-UJI-' . $status->name,
            'nama_customer'  => 'Pelanggan Uji',
            'merk_model'     => 'Asus ROG',
        ]);

        $service = Service::create([
            'nomor_resi'    => (new KodeGenerator())->resi(),
            'perangkat_id'  => $perangkat->id,
            'pegawai_id'    => $this->karyawan->id,
            'keluhan'       => 'Tidak menyala',
            'biaya_service' => 500_000,
            'status'        => $status,
            'tanggal_masuk' => now(),
        ]);

        PartService::create([
            'service_id' => $service->id,
            'nama_part'  => 'SSD 1TB',
            'jumlah'     => 1,
            'harga'      => 300_000,
            'subtotal'   => 300_000,
        ]);

        return $service->fresh();
    }

    private function bearer(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    // ─── Harga servis harus dari server ────────────────────────────

    public function test_checkout_memakai_harga_servis_dari_server_bukan_dari_klien(): void
    {
        $service = $this->buatServis();
        $this->assertSame(800_000, $service->totalBiaya());

        // Klien mencoba menagih servis Rp 800.000 seharga Rp 1.000.
        $this->withHeaders($this->bearer())
            ->postJson('/api/v1/pos/checkout', [
                'items' => [[
                    'tipe' => 'service', 'service_id' => $service->id,
                    'jumlah' => 1, 'harga' => 1_000,
                ]],
                'metode_bayar' => 'Tunai',
                'bayar'        => 800_000,
            ])->assertStatus(201);

        $trx = Transaksi::firstOrFail();
        $this->assertSame(800_000, (int) $trx->total, 'Total wajib mengikuti harga server.');
        $this->assertSame(800_000, (int) $trx->items->first()->harga);
    }

    public function test_sync_memakai_harga_servis_dari_server_bukan_dari_klien(): void
    {
        $service = $this->buatServis();

        $this->withHeaders($this->bearer())
            ->postJson('/api/v1/pos/sync', [
                'local_id'     => 'OFF-1',
                'metode_bayar' => 'Tunai',
                'bayar'        => 800_000,
                'items'        => [[
                    'tipe' => 'service', 'service_id' => $service->id,
                    'nama_item' => 'Servis', 'jumlah' => 1, 'harga' => 0,
                ]],
            ])->assertStatus(200)->assertJsonPath('status', 'success');

        $this->assertSame(800_000, (int) Transaksi::firstOrFail()->total);
    }

    // ─── Guard transisi status servis ──────────────────────────────

    public function test_servis_belum_selesai_tidak_bisa_ditandai_diambil(): void
    {
        $service = $this->buatServis(StatusService::Diterima);

        $this->withHeaders($this->bearer())
            ->postJson('/api/v1/pos/checkout', [
                'items' => [[
                    'tipe' => 'service', 'service_id' => $service->id,
                    'jumlah' => 1, 'harga' => 800_000,
                ]],
                'metode_bayar' => 'Tunai',
                'bayar'        => 800_000,
            ])->assertStatus(422);

        $this->assertSame(StatusService::Diterima, $service->fresh()->status);
        $this->assertDatabaseCount('transaksi', 0);
    }

    // ─── Void ──────────────────────────────────────────────────────

    public function test_void_ganda_hanya_mengembalikan_kuota_promo_sekali(): void
    {
        $promo = Promo::create([
            'nama_promo' => 'Diskon Uji', 'kode_promo' => 'UJI10',
            'tipe_promo' => 'Persen', 'besar_promo' => 10,
            'minimal_transaksi' => 0, 'kuota' => 5, 'terpakai' => 3,
            'waktu_mulai' => now()->subDay(), 'waktu_berakhir' => now()->addDay(),
            'aktif' => true,
        ]);

        $trx = Transaksi::create([
            'kode_nota' => '123456', 'pegawai_id' => $this->karyawan->id,
            'promo_id' => $promo->id, 'metode_bayar' => 'Tunai',
            'subtotal' => 100_000, 'diskon' => 10_000, 'total' => 90_000,
            'bayar' => 90_000, 'kembalian' => 0, 'nama_pembeli' => 'Umum',
            'status' => TransaksiStatus::Normal,
        ]);

        $ownerToken = $this->owner->createApiToken('Owner');
        $h = ['Authorization' => 'Bearer ' . $ownerToken];

        $this->withHeaders($h)->postJson("/api/v1/admin/transaksi/{$trx->id}/void")
            ->assertStatus(200);
        $this->assertSame(2, (int) $promo->fresh()->terpakai);

        // Void kedua harus ditolak, kuota tidak boleh berkurang lagi.
        $this->withHeaders($h)->postJson("/api/v1/admin/transaksi/{$trx->id}/void")
            ->assertStatus(422);
        $this->assertSame(2, (int) $promo->fresh()->terpakai);
    }

    // ─── Batas promo persen di API ─────────────────────────────────

    public function test_api_menolak_promo_persen_di_atas_seratus(): void
    {
        $ownerToken = $this->owner->createApiToken('Owner');

        $this->withHeaders(['Authorization' => 'Bearer ' . $ownerToken])
            ->postJson('/api/v1/admin/promos', [
                'nama_promo' => 'Gratis', 'kode_promo' => 'GRATIS',
                'tipe_promo' => 'Persen', 'besar_promo' => 1000,
                'waktu_mulai' => now()->toDateString(),
                'waktu_berakhir' => now()->addDay()->toDateString(),
            ])->assertStatus(422);
    }
}
