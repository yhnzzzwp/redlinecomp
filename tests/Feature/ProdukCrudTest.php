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
            'nama_produk' => 'RTX 5090', 'sku' => 'RL-NV-5090', 'show_katalog' => '1',
        ]);

        $response->assertRedirect(route('produk.index'));
        // Kolom harga dan jumlah_produk dihapus migrasi 2026_08_20_000003:
        // harga jual ditentukan kasir saat transaksi, stok tidak lagi dilacak.
        $this->assertDatabaseHas('produk', ['sku' => 'RL-NV-5090', 'nama_produk' => 'RTX 5090']);
    }

    public function test_sku_otomatis_di_generate_jika_dikosongkan(): void
    {
        $response = $this->actingAs($this->staff)->post(route('produk.store'), [
            'nama_produk' => 'Monitor Gaming 144Hz',
            'sku' => '',
        ]);

        $response->assertRedirect(route('produk.index'));
        $produk = Produk::query()->where('nama_produk', 'Monitor Gaming 144Hz')->first();
        $this->assertNotNull($produk);
        $this->assertNotNull($produk->sku);
        $this->assertStringStartsWith('RL-PRD-', $produk->sku);
    }

    public function test_validasi_menolak_produk_tanpa_nama(): void
    {
        // Hanya nama_produk yang wajib sekarang; harga bukan lagi kolom produk.
        $this->actingAs($this->staff)
            ->post(route('produk.store'), [])
            ->assertSessionHasErrors(['nama_produk']);

        $this->assertDatabaseCount('produk', 0);
    }

    public function test_sku_tidak_boleh_duplikat(): void
    {
        Produk::create(['nama_produk' => 'A', 'sku' => 'DUP']);

        $this->actingAs($this->staff)
            ->post(route('produk.store'), ['nama_produk' => 'B', 'sku' => 'DUP'])
            ->assertSessionHasErrors('sku');
    }

    public function test_staff_bisa_mengubah_dan_menghapus_produk(): void
    {
        $produk = Produk::create(['nama_produk' => 'Lama']);

        $this->actingAs($this->staff)->put(route('produk.update', $produk), [
            'nama_produk' => 'Baru',
        ])->assertRedirect(route('produk.index'));
        $this->assertDatabaseHas('produk', ['id' => $produk->id, 'nama_produk' => 'Baru']);

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
            ['nama_produk', 'sku', 'kategori', 'deskripsi'],
            [
                ['Keyboard Mechanical RGB', 'RL-KEY-001', 'Aksesori', 'Switch blue'],
                ['Mouse Gaming Wireless', 'RL-MOU-002', 'Aksesori', '16000 DPI'],
            ],
        );

        $response = $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file]);

        $response->assertRedirect(route('produk.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('produk', ['nama_produk' => 'Keyboard Mechanical RGB', 'sku' => 'RL-KEY-001']);
        $this->assertDatabaseHas('produk', ['nama_produk' => 'Mouse Gaming Wireless', 'sku' => 'RL-MOU-002']);
        $this->assertDatabaseHas('kategori_produk', ['nama_kategori' => 'Aksesori']);
    }

    public function test_impor_excel_toleran_header_alias_dan_format_rupiah(): void
    {
        $file = $this->buatFileExcel(
            ['Barang', 'Kode Barang', 'Jenis', 'Keterangan'],
            [['Headset Gaming 7.1', 'HS-009', 'Audio', 'Surround Sound']],
        );

        $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file])
            ->assertRedirect(route('produk.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('produk', [
            'nama_produk' => 'Headset Gaming 7.1', 'sku' => 'HS-009',
        ]);
        $this->assertDatabaseHas('kategori_produk', ['nama_kategori' => 'Audio']);
    }

    public function test_impor_excel_memperbarui_produk_dengan_sku_sama(): void
    {
        Produk::create(['nama_produk' => 'SSD Lama', 'sku' => 'RL-SSD-01', 'show_katalog' => true]);

        $file = $this->buatFileExcel(
            ['nama_produk', 'sku', 'kategori', 'deskripsi'],
            [['SSD Samsung 980 500GB', 'RL-SSD-01', '', '']],
        );

        $this->actingAs($this->staff)->post(route('produk.import'), ['file_excel' => $file])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('produk', 1);
        $this->assertDatabaseHas('produk', ['sku' => 'RL-SSD-01', 'nama_produk' => 'SSD Samsung 980 500GB']);
    }

    public function test_impor_excel_dibatalkan_seluruhnya_bila_ada_baris_bermasalah(): void
    {
        $file = $this->buatFileExcel(
            ['nama_produk', 'sku', 'kategori', 'deskripsi'],
            [
                ['Produk Valid', 'RL-OK-01', '', ''],
                ['', 'RL-NO-NAME', '', 'tanpa nama'],
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
