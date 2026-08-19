<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\HasilImporProduk;
use App\Exceptions\ImporProdukException;
use App\Models\KategoriProduk;
use App\Models\Produk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ProdukExcelService
{
    public function __construct() {}

    private const KOLOM = ['nama_produk', 'sku', 'kategori', 'deskripsi'];

    private const MAKS_BARIS = 2000;

    private const ALIAS = [
        'nama' => ['nama_produk', 'nama', 'product_name', 'product', 'nama_barang', 'barang', 'item', 'title', 'nama_item'],
        'sku' => ['sku', 'kode', 'code', 'barcode', 'kode_produk', 'kode_barang', 'sku_code', 'part_number'],
        'kategori' => ['kategori', 'category', 'cat', 'jenis', 'kategori_produk', 'kelompok'],
        'deskripsi' => ['deskripsi', 'deskripsi_produk', 'description', 'ket', 'keterangan', 'detail', 'notes'],
    ];

    public function import(UploadedFile $file): HasilImporProduk
    {
        $sheet = IOFactory::load($file->getRealPath())->getSheet(0);

        if ($sheet->getHighestDataRow() > self::MAKS_BARIS + 1) {
            throw new ImporProdukException(['File melebihi batas ' . number_format(self::MAKS_BARIS, 0, ',', '.') . ' baris data. Pecah menjadi beberapa file.']);
        }

        $rows = $sheet->toArray(null, true, true, false);
        if (count($rows) < 2) {
            throw new ImporProdukException(['File kosong — tidak ada baris data di bawah header.']);
        }

        $idx = $this->petakanHeader(array_shift($rows));

        $galat = [];
        $barisValid = [];
        $skuTerlihat = [];
        foreach ($rows as $i => $row) {
            $nomor = $i + 2; 

            $ambil = fn (string $k): string => trim((string) ($row[$idx[$k]] ?? ''));
            if ($ambil('nama') === '' && $ambil('sku') === '') {
                continue; 
            }

            if ($ambil('nama') === '') {
                $galat[] = "Baris {$nomor}: nama produk kosong.";
                continue;
            }

            $sku = $ambil('sku');
            if ($sku !== '') {
                if (isset($skuTerlihat[$sku])) {
                    $galat[] = "Baris {$nomor}: SKU \"{$sku}\" duplikat dengan baris {$skuTerlihat[$sku]}.";
                }
                $skuTerlihat[$sku] = $nomor;
            }

            $barisValid[] = [
                'nama' => $ambil('nama'),
                'sku' => $sku,
                'kategori' => $ambil('kategori'),
                'deskripsi' => $ambil('deskripsi') !== '' ? $ambil('deskripsi') : null,
            ];
        }

        if ($galat !== []) {
            throw new ImporProdukException($galat);
        }

        if ($barisValid === []) {
            throw new ImporProdukException(['Tidak ada data produk yang valid dalam file.']);
        }

        $baru = 0;
        $diperbarui = 0;
        $kategoriBaru = 0;

        DB::transaction(function () use ($barisValid, &$baru, &$diperbarui, &$kategoriBaru): void {
            foreach ($barisValid as $b) {
                $kategoriId = null;
                if ($b['kategori'] !== '') {
                    $kategori = KategoriProduk::query()->firstOrCreate(['nama_kategori' => $b['kategori']]);
                    $kategoriId = $kategori->id;
                    if ($kategori->wasRecentlyCreated) {
                        $kategoriBaru++;
                    }
                }

                $existing = $b['sku'] !== ''
                    ? Produk::query()->where('sku', $b['sku'])->first()
                    : Produk::query()->where('nama_produk', $b['nama'])->first();

                if ($existing) {
                    $existing->update([
                        'nama_produk' => $b['nama'],
                        'kategori_id' => $kategoriId ?? $existing->kategori_id,
                        'deskripsi_produk' => $b['deskripsi'] ?? $existing->deskripsi_produk,
                    ]);
                    $diperbarui++;
                } else {
                    $produkBaru = Produk::query()->create([
                        'nama_produk' => $b['nama'],
                        'sku' => $b['sku'] !== '' ? $b['sku'] : ('RL-PRD-' . strtoupper(Str::random(6))),
                        'kategori_id' => $kategoriId,
                        'deskripsi_produk' => $b['deskripsi'],
                        'show_katalog' => true,
                    ]);
                    $baru++;
                }
            }
        });

        return new HasilImporProduk($baru, $diperbarui, $kategoriBaru);
    }

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Produk');

        $this->tulisHeader($sheet);
        $contoh = [
            ['Processor Intel Core i5-13400F', 'RL-PROC-001', 'Processors', 'LGA1700 Gen 13'],
            ['RAM Corsair Vengeance 16GB DDR4', 'RL-RAM-002', 'Peripherals', 'PC DDR4 3200MHz'],
            ['SSD Samsung 980 NVMe 500GB', 'RL-SSD-003', 'Storage', 'M.2 NVMe PCIe 3.0'],
        ];
        foreach ($contoh as $r => $baris) {
            $this->tulisBaris($sheet, $r + 2, $baris);
        }

        $this->pasangDropdownKategori($sheet);
        $this->tulisPetunjuk($spreadsheet);

        return $spreadsheet;
    }

    public function ekspor(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Produk');

        $this->tulisHeader($sheet);

        $r = 2;

        foreach (Produk::query()->with('kategori')->orderBy('nama_produk')->lazy(500) as $p) {
            $this->tulisBaris($sheet, $r++, [
                $p->nama_produk,
                $p->sku ?? '',
                $p->kategori->nama_kategori ?? '',
                $p->deskripsi_produk ?? '',
            ]);
        }

        $this->pasangDropdownKategori($sheet);

        return $spreadsheet;
    }

    private function petakanHeader(array $header): array
    {
        $peta = [];
        foreach ($header as $i => $nama) {
            $peta[strtolower(trim(str_replace(['_', '-', ' '], '', (string) $nama)))] = $i;
        }

        $cari = function (array $aliases, int $default) use ($peta): int {
            foreach ($aliases as $alias) {
                $kunci = strtolower(str_replace(['_', '-', ' '], '', $alias));
                if (isset($peta[$kunci])) {
                    return $peta[$kunci];
                }
            }

            return $default;
        };

        return [
            'nama' => $cari(self::ALIAS['nama'], 0),
            'sku' => $cari(self::ALIAS['sku'], 1),
            'kategori' => $cari(self::ALIAS['kategori'], 2),
            'deskripsi' => $cari(self::ALIAS['deskripsi'], 3),
        ];
    }

    private function tulisHeader(Worksheet $sheet): void
    {
        foreach (self::KOLOM as $i => $kolom) {
            $sheet->setCellValue([$i + 1, 1], $kolom);
        }
        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:D1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF15181E');
        foreach (['A' => 34, 'B' => 16, 'C' => 18, 'D' => 40] as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }
        $sheet->freezePane('A2');
    }

    private function tulisBaris(Worksheet $sheet, int $baris, array $data): void
    {
        $teks = [0, 1, 2, 3];
        foreach ($data as $i => $nilai) {
            $koordinat = [$i + 1, $baris];
            if (in_array($i, $teks, true)) {
                $nilai = (string) $nilai;
                $sheet->getCell($koordinat)->setValueExplicit($nilai, DataType::TYPE_STRING);
                if ($nilai !== '' && str_contains('=+-@', $nilai[0])) {
                    $sheet->getStyle($koordinat)->setQuotePrefix(true);
                }
            } else {
                $sheet->getCell($koordinat)->setValueExplicit((string) (int) $nilai, DataType::TYPE_NUMERIC);
            }
        }
    }

    private function pasangDropdownKategori(Worksheet $sheet): void
    {
        $daftar = KategoriProduk::query()->orderBy('nama_kategori')->pluck('nama_kategori')->all();
        if ($daftar === []) {
            return;
        }

        $formula = '"' . implode(',', $daftar) . '"';
        if (strlen($formula) > 255) {
            return;
        }

        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_INFORMATION)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setFormula1($formula);

        $sheet->setDataValidation('C2:C' . (self::MAKS_BARIS + 1), $validation);
    }

    private function tulisPetunjuk(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('Petunjuk');
        $baris = [
            ['Petunjuk Pengisian — Impor Produk Redline Komputer'],
            [''],
            ['1. Isi data mulai baris ke-2 sheet "Produk". Baris contoh boleh ditimpa atau dihapus.'],
            ['2. Kolom nama_produk wajib diisi; kolom lain boleh kosong.'],
            ['3. SKU yang sudah ada di sistem akan MEMPERBARUI produk tersebut; SKU baru/kosong membuat produk baru.'],
            ['4. Kategori baru dibuat otomatis; gunakan dropdown untuk kategori yang sudah ada.'],
            ['5. Maksimal ' . number_format(self::MAKS_BARIS, 0, ',', '.') . ' baris per file. Jika ada baris bermasalah, seluruh impor dibatalkan.'],
            [''],
            ['Kategori tersedia saat ini:'],
        ];
        foreach (KategoriProduk::query()->orderBy('nama_kategori')->pluck('nama_kategori') as $k) {
            $baris[] = ['- ' . $k];
        }
        foreach ($baris as $r => $isi) {
            $sheet->getCell([1, $r + 1])->setValueExplicit((string) $isi[0], DataType::TYPE_STRING);
        }
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(100);
        $spreadsheet->setActiveSheetIndex(0);
    }
}
