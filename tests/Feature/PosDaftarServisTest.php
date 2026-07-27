<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\CartLine;
use App\Data\CheckoutData;
use App\Enums\MetodeBayar;
use App\Enums\StatusService;
use App\Enums\TipeItem;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Service;
use App\Services\PosService;
use App\Services\ServiceTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Mengunci kontrak layar POS untuk item servis:
 * harga yang DILIHAT kasir harus sama dengan yang DITAGIH server, dan servis
 * yang siap diambil harus benar-benar bisa dipilih.
 */
final class PosDaftarServisTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('staff');

        $this->kasir = Pegawai::create([
            'nama_pegawai' => 'Kasir Uji', 'username' => 'kasirpos', 'email' => 'kasirpos@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    private function servis(string $barang, int $jasa, StatusService $status = StatusService::Selesai): Service
    {
        $servis = app(ServiceTicketService::class)->buat([
            'nama_customer' => 'Budi', 'nama_barang' => $barang, 'masalah' => 'Mati total',
        ], $this->kasir);

        $servis->update(['biaya_service' => $jasa, 'status' => $status]);

        return $servis->refresh();
    }

    /**
     * Item yang dikirim halaman POS ke keranjang (payload Alpine di atribut x-data).
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemDiLayarPos(): array
    {
        $html = $this->actingAs($this->kasir)->get(route('pos'))->assertOk()->getContent();

        // @js() menghasilkan JSON.parse('…') dengan tanda kutip ganda ditulis
        // " supaya aman di dalam atribut; kutip tunggal jadi ' sehingga
        // penutup ') tidak pernah ambigu.
        preg_match("/pos\(JSON\.parse\('(.*?)'\)/s", html_entity_decode((string) $html, ENT_QUOTES), $m);
        $this->assertNotEmpty($m[1] ?? '', 'Payload item POS tidak ditemukan di atribut x-data.');

        // Kutip pembatas ditulis sebagai escape " — kembalikan ke " agar
        // json_decode mau memakannya (chr(92) = backslash).
        $items = json_decode(str_replace(chr(92).'u0022', '"', $m[1]), true);
        $this->assertIsArray($items);

        return $items;
    }

    public function test_harga_servis_di_layar_pos_sama_dengan_yang_ditagih_server(): void
    {
        $part = Produk::create([
            'nama_produk' => 'RAM 8GB', 'jumlah_produk' => 5, 'harga' => 400_000,
            'harga_modal' => 300_000, 'show_katalog' => false,
        ]);

        $servis = $this->servis('Laptop Acer', 150_000);
        app(ServiceTicketService::class)->tambahPart($servis, [
            'produk_id' => $part->id, 'nama_part' => 'RAM 8GB', 'jumlah' => 1, 'harga' => 400_000,
        ]);
        $servis->refresh();

        $baris = collect($this->itemDiLayarPos())->firstWhere('tipe', 'service');
        $this->assertNotNull($baris, 'Servis tidak muncul di layar POS.');

        // Jasa 150.000 + part 400.000 — bukan biaya_service saja.
        $this->assertSame(550_000, $servis->totalBiaya());
        $this->assertSame($servis->totalBiaya(), $baris['harga']);

        // Kasir membayar persis angka di layar → harus diterima, kembalian 0.
        $trx = app(PosService::class)->checkout(new CheckoutData(
            items: [new CartLine(TipeItem::Servis->value, $servis->id, 1)],
            metodeBayar: MetodeBayar::Tunai,
            bayar: (int) $baris['harga'],
        ), $this->kasir);

        $this->assertSame(550_000, $trx->total);
        $this->assertSame(0, $trx->kembalian);
    }

    public function test_servis_selesai_bisa_dipilih_dan_yang_sudah_diambil_tidak(): void
    {
        $siap = $this->servis('Laptop Siap Ambil', 200_000);
        $dikerjakan = $this->servis('Laptop Dikerjakan', 100_000, StatusService::Dikerjakan);
        $selesaiDiambil = $this->servis('Laptop Lama', 300_000, StatusService::SudahDiambil);

        $servisDiLayar = collect($this->itemDiLayarPos())->where('tipe', 'service');
        $resi = $servisDiLayar->pluck('real_id');

        $this->assertTrue($resi->contains($siap->id), 'Servis Selesai wajib bisa ditagih di POS.');
        $this->assertTrue($resi->contains($dikerjakan->id), 'Servis berjalan tetap boleh ditagih.');
        $this->assertFalse($resi->contains($selesaiDiambil->id), 'Servis yang sudah diambil tidak boleh muncul lagi.');

        // Yang siap diambil ditaruh paling atas agar kasir tidak salah pilih.
        $this->assertSame($siap->id, $servisDiLayar->first()['real_id']);
        $this->assertStringContainsString('siap diambil', $servisDiLayar->first()['info']);
    }
}
