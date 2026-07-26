<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Pegawai;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProdukCrudTest extends TestCase
{
    use RefreshDatabase;

    private Pegawai $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usePortal('staff');
        $this->staff = Pegawai::create([
            'nama_pegawai' => 'Staff Uji', 'username' => 'staff', 'email' => 'staff@uji.test',
            'password' => Hash::make('password'), 'role' => 'Karyawan', 'masih_bekerja' => true,
        ]);
    }

    public function test_tamu_tidak_bisa_akses_manajemen_produk(): void
    {
        $this->get(route('produk.index'))->assertRedirect(route('login'));
    }

    public function test_staff_bisa_menambah_produk(): void
    {
        $response = $this->actingAs($this->staff)->post(route('produk.store'), [
            'nama_produk' => 'RTX 5090', 'sku' => 'RL-NV-5090', 'harga' => 30_000_000,
            'jumlah_produk' => 4, 'show_katalog' => '1',
        ]);

        $response->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('produk', ['sku' => 'RL-NV-5090', 'harga' => 30_000_000, 'jumlah_produk' => 4]);
    }

    public function test_sku_otomatis_di_generate_jika_dikosongkan(): void
    {
        $response = $this->actingAs($this->staff)->post(route('produk.store'), [
            'nama_produk' => 'Monitor Gaming 144Hz',
            'sku' => '',
            'harga' => 2_500_000,
            'jumlah_produk' => 5,
        ]);

        $response->assertRedirect(route('produk.index'));
        $produk = Produk::query()->where('nama_produk', 'Monitor Gaming 144Hz')->first();
        $this->assertNotNull($produk);
        $this->assertNotNull($produk->sku);
        $this->assertStringStartsWith('RL-PRD-', $produk->sku);
    }

    public function test_validasi_menolak_produk_tanpa_nama_dan_harga(): void
    {
        $this->actingAs($this->staff)
            ->post(route('produk.store'), ['jumlah_produk' => 1])
            ->assertSessionHasErrors(['nama_produk', 'harga']);

        $this->assertDatabaseCount('produk', 0);
    }

    public function test_sku_tidak_boleh_duplikat(): void
    {
        Produk::create(['nama_produk' => 'A', 'sku' => 'DUP', 'harga' => 1, 'jumlah_produk' => 1]);

        $this->actingAs($this->staff)
            ->post(route('produk.store'), ['nama_produk' => 'B', 'sku' => 'DUP', 'harga' => 1, 'jumlah_produk' => 1])
            ->assertSessionHasErrors('sku');
    }

    public function test_staff_bisa_mengubah_dan_menghapus_produk(): void
    {
        $produk = Produk::create(['nama_produk' => 'Lama', 'harga' => 1_000, 'jumlah_produk' => 5]);

        $this->actingAs($this->staff)->put(route('produk.update', $produk), [
            'nama_produk' => 'Baru', 'harga' => 2_000, 'jumlah_produk' => 9,
        ])->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'nama_produk' => 'Baru', 'harga' => 2_000]);

        $this->actingAs($this->staff)->delete(route('produk.destroy', $produk))
            ->assertRedirect(route('produk.index'));
        $this->assertDatabaseMissing('produk', ['id' => $produk->id]);
    }

    public function test_staff_bisa_unduh_template_dan_ekspor_excel(): void
    {
        $xlsxMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $this->actingAs($this->staff)->get(route('produk.template'))
            ->assertOk()->assertHeader('Content-Type', $xlsxMime);

        $this->actingAs($this->staff)->get(route('produk.export'))
            ->assertOk()->assertHeader('Content-Type', $xlsxMime);
    }

    public function test_staff_bisa_impor_produk_massal_via_excel(): void
    {
        $file = $this->buatFileExcel(
            ['nama_produk', 'sku', 'kategori', 'harga', 'harga_modal', 'jumlah_produk', 'deskripsi'],
            [
                ['Keyboard Mechanical RGB', 'RL-KEY-001', 'Aksesori', 450000, 380000, 15, 'Switch blue'],
                ['Mouse Gaming Wireless', 'RL-MOU-002', 'Aksesori', 250000, 200000, 20, '16000 DPI'],
            ],
        );

        $response = $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file]);

        $response->assertRedirect(route('produk.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('produk', ['nama_produk' => 'Keyboard Mechanical RGB', 'sku' => 'RL-KEY-001', 'harga' => 450000, 'jumlah_produk' => 15]);
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Mouse Gaming Wireless', 'sku' => 'RL-MOU-002', 'harga' => 250000, 'jumlah_produk' => 20]);
        $this->assertDatabaseHas('kategori_produk', ['nama_kategori' => 'Aksesori']);
    }

    public function test_impor_excel_toleran_header_alias_dan_format_rupiah(): void
    {
        $file = $this->buatFileExcel(
            ['Barang', 'Kode Barang', 'Jenis', 'Harga Jual', 'HPP', 'QTY', 'Keterangan'],
            [['Headset Gaming 7.1', 'HS-009', 'Audio', 'Rp 350.000', 'Rp 250.000', 12, 'Surround Sound']],
        );

        $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file])
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Headset Gaming 7.1', 'sku' => 'HS-009',
            'harga' => 350000, 'harga_modal' => 250000, 'jumlah_produk' => 12,
        ]);
    }

    public function test_impor_excel_memperbarui_produk_dengan_sku_sama(): void
    {
        Produk::create(['nama_produk' => 'SSD Lama', 'sku' => 'RL-SSD-01', 'harga' => 500000, 'jumlah_produk' => 5, 'show_katalog' => true]);

        $file = $this->buatFileExcel(
            ['nama_produk', 'sku', 'kategori', 'harga', 'harga_modal', 'jumlah_produk', 'deskripsi'],
            [['SSD Samsung 980 500GB', 'RL-SSD-01', '', 850000, 700000, 9, '']],
        );

        $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('produk', 1);
        $this->assertDatabaseHas('produk', ['sku' => 'RL-SSD-01', 'nama_produk' => 'SSD Samsung 980 500GB', 'harga' => 850000, 'jumlah_produk' => 9]);
    }

    public function test_impor_excel_dibatalkan_seluruhnya_bila_ada_baris_bermasalah(): void
    {
        $file = $this->buatFileExcel(
            ['nama_produk', 'sku', 'kategori', 'harga', 'harga_modal', 'jumlah_produk', 'deskripsi'],
            [
                ['Produk Valid', 'RL-OK-01', '', 100000, 90000, 5, ''],
                ['', 'RL-NO-NAME', '', 50000, 40000, 2, 'tanpa nama'],
            ],
        );

        $response = $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file]);

        $response->assertSessionHasErrors('file_excel');
        $response->assertSessionHas('import_baris_gagal');
        $this->assertDatabaseCount('produk', 0);
    }

    public function test_impor_menolak_file_csv_karena_sudah_diganti_excel(): void
    {
        $csv = UploadedFile::fake()->createWithContent('produk.csv', "nama_produk,sku\nTes,SKU-1\n");

        $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $csv])
            ->assertSessionHasErrors('file_excel');

        $this->assertDatabaseCount('produk', 0);
    }

    private function buatFileExcel(array $header, array $rows): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($header, null, 'A1');
        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $path = tempnam(sys_get_temp_dir(), 'uji-produk') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'produk.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
