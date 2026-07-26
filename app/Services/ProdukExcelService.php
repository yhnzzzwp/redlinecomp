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

/**
 * Impor & ekspor produk berbasis Excel (.xlsx/.xls) — menggantikan alur CSV.
 *
 * Alur kerja Owner: Ekspor Produk -> sesuaikan di Excel -> Impor kembali.
 * Impor bersifat ALL-OR-NOTHING: seluruh baris divalidasi lebih dulu; bila ada
 * baris bermasalah, tidak ada satu pun yang ditulis ke database dan daftar
 * galat per baris dikembalikan lewat ImporProdukException.
 */
final class ProdukExcelService
{
    private const KOLOM = ['nama_produk', 'sku', 'kategori', 'harga', 'harga_modal', 'jumlah_produk', 'deskripsi'];

    private const MAKS_BARIS = 2000;

    /** Header fleksibel: berbagai penamaan umum dipetakan ke kolom baku. */
    private const ALIAS = [
        'nama' => ['nama_produk', 'nama', 'product_name', 'product', 'nama_barang', 'barang', 'item', 'title', 'nama_item'],
        'sku' => ['sku', 'kode', 'code', 'barcode', 'kode_produk', 'kode_barang', 'sku_code', 'part_number'],
        'kategori' => ['kategori', 'category', 'cat', 'jenis', 'kategori_produk', 'kelompok'],
        'harga' => ['harga', 'harga_jual', 'price', 'sell_price', 'harga_produk', 'nominal', 'price_idr'],
        'harga_modal' => ['harga_modal', 'modal', 'cost', 'hpp', 'buy_price', 'harga_beli', 'cost_price'],
        'jumlah' => ['jumlah_produk', 'stok', 'stock', 'jumlah', 'qty', 'quantity', 'sisa', 'sisa_stok'],
        'deskripsi' => ['deskripsi', 'deskripsi_produk', 'description', 'ket', 'keterangan', 'detail', 'notes'],
    ];

    /* ================================================================
     * IMPOR
     * ================================================================ */

    /**
     * @throws ImporProdukException bila file tidak berisi data valid.
     */
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

        // Tahap 1 — validasi seluruh baris tanpa menyentuh database.
        $galat = [];
        $barisValid = [];
        $skuTerlihat = [];
        foreach ($rows as $i => $row) {
            $nomor = $i + 2; // +1 header, +1 basis-1 Excel

            $ambil = fn (string $k): string => trim((string) ($row[$idx[$k]] ?? ''));
            if ($ambil('nama') === '' && $ambil('sku') === '' && $ambil('harga') === '' && $ambil('jumlah') === '') {
                continue; // baris benar-benar kosong
            }

            if ($ambil('nama') === '') {
                $galat[] = "Baris {$nomor}: nama produk kosong.";
                continue;
            }

            foreach (['harga' => 'harga', 'harga_modal' => 'harga modal', 'jumlah' => 'jumlah stok'] as $k => $label) {
                $v = $ambil($k);
                if ($v !== '' && preg_replace('/[^0-9]/', '', $v) === '') {
                    $galat[] = "Baris {$nomor}: {$label} \"{$v}\" bukan angka.";
                }
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
                'harga' => $this->keAngka($ambil('harga'), 10_000_000_000),
                'harga_modal' => $this->keAngka($ambil('harga_modal'), 10_000_000_000),
                'jumlah' => $this->keAngka($ambil('jumlah'), 1_000_000),
                'deskripsi' => $ambil('deskripsi') !== '' ? $ambil('deskripsi') : null,
            ];
        }

        if ($galat !== []) {
            throw new ImporProdukException($galat);
        }

        if ($barisValid === []) {
            throw new ImporProdukException(['Tidak ada data produk yang valid dalam file.']);
        }

        // Tahap 2 — tulis semuanya dalam satu transaksi.
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
                        'harga' => $b['harga'] > 0 ? $b['harga'] : $existing->harga,
                        'harga_modal' => $b['harga_modal'] > 0 ? $b['harga_modal'] : $existing->harga_modal,
                        'jumlah_produk' => $b['jumlah'] > 0 ? $b['jumlah'] : $existing->jumlah_produk,
                        'deskripsi_produk' => $b['deskripsi'] ?? $existing->deskripsi_produk,
                    ]);
                    $diperbarui++;
                } else {
                    Produk::query()->create([
                        'nama_produk' => $b['nama'],
                        'sku' => $b['sku'] !== '' ? $b['sku'] : ('RL-PRD-' . strtoupper(Str::random(6))),
                        'kategori_id' => $kategoriId,
                        'harga' => $b['harga'],
                        'harga_modal' => $b['harga_modal'],
                        'jumlah_produk' => $b['jumlah'],
                        'deskripsi_produk' => $b['deskripsi'],
                        'show_katalog' => true,
                    ]);
                    $baru++;
                }
            }
        });

        return new HasilImporProduk($baru, $diperbarui, $kategoriBaru);
    }

    /* ================================================================
     * TEMPLATE & EKSPOR
     * ================================================================ */

    public function template(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Produk');

        $this->tulisHeader($sheet);
        $contoh = [
            ['Processor Intel Core i5-13400F', 'RL-PROC-001', 'Processors', 3100000, 2850000, 10, 'LGA1700 Gen 13'],
            ['RAM Corsair Vengeance 16GB DDR4', 'RL-RAM-002', 'Peripherals', 950000, 820000, 25, 'PC DDR4 3200MHz'],
            ['SSD Samsung 980 NVMe 500GB', 'RL-SSD-003', 'Storage', 850000, 750000, 15, 'M.2 NVMe PCIe 3.0'],
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
        foreach (Produk::query()->with('kategori')->orderBy('nama_produk')->cursor() as $p) {
            $this->tulisBaris($sheet, $r++, [
                $p->nama_produk,
                $p->sku ?? '',
                $p->kategori->nama_kategori ?? '',
                (int) $p->harga,
                (int) $p->harga_modal,
                (int) $p->jumlah_produk,
                $p->deskripsi_produk ?? '',
            ]);
        }

        $this->pasangDropdownKategori($sheet);

        return $spreadsheet;
    }

    /* ================================================================
     * Helper privat
     * ================================================================ */

    /** @return array<string,int> peta kolom-baku -> indeks kolom di file */
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
            'harga' => $cari(self::ALIAS['harga'], 3),
            'harga_modal' => $cari(self::ALIAS['harga_modal'], 4),
            'jumlah' => $cari(self::ALIAS['jumlah'], 5),
            'deskripsi' => $cari(self::ALIAS['deskripsi'], 6),
        ];
    }

    /** "Rp 3.100.000" -> 3100000, dibatasi 0..$maks. */
    private function keAngka(string $nilai, int $maks): int
    {
        $angka = (int) preg_replace('/[^0-9]/', '', $nilai);

        return min($maks, max(0, $angka));
    }

    private function tulisHeader(Worksheet $sheet): void
    {
        foreach (self::KOLOM as $i => $kolom) {
            $sheet->setCellValue([$i + 1, 1], $kolom);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:G1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF15181E');
        foreach (['A' => 34, 'B' => 16, 'C' => 18, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 40] as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }
        $sheet->freezePane('A2');
    }

    /**
     * Tulis satu baris data. Kolom teks ditulis sebagai STRING eksplisit
     * (+ quote prefix untuk sel berawalan = + - @) agar isi sel tidak pernah
     * dieksekusi Excel sebagai formula (mitigasi formula injection).
     */
    private function tulisBaris(Worksheet $sheet, int $baris, array $data): void
    {
        $teks = [0, 1, 2, 6];
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

    /** Dropdown kategori pada kolom C (hanya bila daftar muat di batas 255 karakter Excel). */
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
            ['4. Harga/stok cukup angka (mis. 3100000). Format "Rp 3.100.000" juga diterima.'],
            ['5. Kategori baru dibuat otomatis; gunakan dropdown untuk kategori yang sudah ada.'],
            ['6. Maksimal ' . number_format(self::MAKS_BARIS, 0, ',', '.') . ' baris per file. Jika ada baris bermasalah, seluruh impor dibatalkan.'],
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
