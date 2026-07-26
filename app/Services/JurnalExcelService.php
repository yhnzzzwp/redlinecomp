<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TipeItem;
use App\Enums\TransaksiStatus;
use App\Models\ItemTransaksi;
use App\Models\Produk;
use App\Models\Transaksi;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ekspor Jurnal Akuntansi (.xlsx) — jurnal umum double-entry per transaksi,
 * siap diimpor/dipetakan ke software akuntansi (Accurate, Zahir, Jurnal.id).
 *
 * Per transaksi berstatus Normal pada periode:
 *   Debit  Kas/Bank/QRIS (per metode bayar)  = total (bila > 0)
 *   Debit  Diskon Penjualan                  = diskon (bila ada)
 *   Kredit Pendapatan Penjualan Produk       = Σ subtotal item Produk
 *   Kredit Pendapatan Jasa Servis            = Σ subtotal item Servis
 *   Debit  HPP  /  Kredit Persediaan         = Σ (snapshot harga_modal × qty)
 *                                              — produk & part servis
 *
 * Setiap blok dijamin seimbang (total + diskon = Σ subtotal item; HPP berpasangan).
 * Transaksi Void/Refund dikecualikan. Bagan akun dari config/redline.php ('akun').
 */
final class JurnalExcelService
{
    /** Metode bayar → kunci akun di config redline.akun. */
    private const METODE_KE_AKUN = ['Tunai' => 'kas', 'Transfer' => 'bank', 'QRIS' => 'qris'];

    private const HEADER = ['Tanggal', 'No Bukti', 'Kode Akun', 'Nama Akun', 'Keterangan', 'Debit', 'Kredit'];

    public function ekspor(Carbon $dari, Carbon $sampai): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $jurnal = $spreadsheet->getActiveSheet()->setTitle('Jurnal');
        $this->tulisHeader($jurnal);

        /** @var array<string, array{kode: string, nama: string, debit: int, kredit: int}> $rekap */
        $rekap = [];
        $baris = 2;
        $jumlahTrx = 0;

        // lazy() (bukan cursor()) supaya eager load items.produk benar-benar
        // berjalan per halaman — cursor() diam-diam mengabaikan with().
        $query = Transaksi::query()
            ->with(['items.produk'])
            ->whereBetween('created_at', [$dari, $sampai])
            ->where('status', TransaksiStatus::Normal->value)
            ->orderBy('created_at');

        foreach ($query->lazy(500) as $trx) {
            $jumlahTrx++;
            foreach ($this->barisJurnal($trx) as $b) {
                [$kunciAkun, $keterangan, $debit, $kredit] = $b;
                [$kode, $nama] = $this->akun($kunciAkun);

                $this->tulisBaris($jurnal, $baris++, [
                    $trx->created_at?->format('Y-m-d') ?? '-',
                    $trx->kode_nota,
                    $kode,
                    $nama,
                    $keterangan,
                    $debit,
                    $kredit,
                ]);

                $rekap[$kunciAkun] ??= ['kode' => $kode, 'nama' => $nama, 'debit' => 0, 'kredit' => 0];
                $rekap[$kunciAkun]['debit'] += $debit;
                $rekap[$kunciAkun]['kredit'] += $kredit;
            }
        }

        $this->tulisTotal($jurnal, $baris, array_sum(array_column($rekap, 'debit')), array_sum(array_column($rekap, 'kredit')));
        $this->tulisRekap($spreadsheet, $rekap);
        $this->tulisInfo($spreadsheet, $dari, $sampai, $jumlahTrx);

        return $spreadsheet;
    }

    /**
     * Baris jurnal satu transaksi: [kunci akun, keterangan, debit, kredit].
     *
     * @return list<array{string, string, int, int}>
     */
    private function barisJurnal(Transaksi $trx): array
    {
        $penjualanProduk = 0;
        $pendapatanServis = 0;
        $hpp = 0;

        /** @var ItemTransaksi $item */
        foreach ($trx->items as $item) {
            if ($item->tipe === TipeItem::Produk) {
                $penjualanProduk += (int) $item->subtotal;
            } else {
                $pendapatanServis += (int) $item->subtotal;
            }

            // HPP dari SNAPSHOT harga_modal saat checkout (item Servis: total
            // modal part). Baris lama tanpa snapshot: fallback harga_modal
            // produk saat ekspor (Servis lama: 0) — lihat sheet Info.
            $modalSatuan = $item->harga_modal;
            if ($modalSatuan === null && $item->tipe === TipeItem::Produk) {
                /** @var Produk|null $produk */
                $produk = $item->produk;
                $modalSatuan = (int) ($produk->harga_modal ?? 0);
            }
            $hpp += ((int) $modalSatuan) * (int) $item->jumlah;
        }

        $kunciBayar = self::METODE_KE_AKUN[$trx->metode_bayar] ?? 'kas';
        $keterangan = 'Penjualan '.$trx->kode_nota;

        // Baris kas hanya bila ada uang berpindah (transaksi total 0 sah:
        // promo menutup seluruh subtotal) — importer akuntansi menolak baris 0.
        $baris = [];
        if ((int) $trx->total > 0) {
            $baris[] = [$kunciBayar, $keterangan, (int) $trx->total, 0];
        }

        if ((int) $trx->diskon > 0) {
            $baris[] = ['diskon_penjualan', 'Diskon promo — '.$trx->kode_nota, (int) $trx->diskon, 0];
        }
        if ($penjualanProduk > 0) {
            $baris[] = ['penjualan_produk', $keterangan, 0, $penjualanProduk];
        }
        if ($pendapatanServis > 0) {
            $baris[] = ['pendapatan_servis', $keterangan, 0, $pendapatanServis];
        }
        if ($hpp > 0) {
            $baris[] = ['hpp', 'HPP '.$trx->kode_nota, $hpp, 0];
            $baris[] = ['persediaan', 'HPP '.$trx->kode_nota, 0, $hpp];
        }

        return $baris;
    }

    /** @return array{string, string} [kode, nama] dari config redline.akun */
    private function akun(string $kunci): array
    {
        /** @var array{kode?: string, nama?: string} $akun */
        $akun = (array) config('redline.akun.'.$kunci, []);

        return [(string) ($akun['kode'] ?? '?'), (string) ($akun['nama'] ?? $kunci)];
    }

    private function tulisHeader(Worksheet $sheet): void
    {
        foreach (self::HEADER as $i => $judul) {
            $sheet->setCellValue([$i + 1, 1], $judul);
        }
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:G1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF15181E');
        foreach (['A' => 12, 'B' => 18, 'C' => 12, 'D' => 30, 'E' => 30, 'F' => 16, 'G' => 16] as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }
        $sheet->freezePane('A2');
    }

    /**
     * Kolom teks sebagai STRING eksplisit (+ quote prefix untuk sel berawalan
     * = + - @) agar tidak pernah dieksekusi sebagai formula — pola yang sama
     * dengan ProdukExcelService (mitigasi formula injection).
     *
     * @param array<int, string|int> $data
     */
    private function tulisBaris(Worksheet $sheet, int $baris, array $data): void
    {
        $teks = [0, 1, 2, 3, 4];
        foreach ($data as $i => $nilai) {
            $koordinat = [$i + 1, $baris];
            if (in_array($i, $teks, true)) {
                $this->tulisTeks($sheet, $koordinat, (string) $nilai);
            } else {
                $sheet->getCell($koordinat)->setValueExplicit((string) (int) $nilai, DataType::TYPE_NUMERIC);
                $sheet->getStyle($koordinat)->getNumberFormat()->setFormatCode('#,##0');
            }
        }
    }

    /**
     * Sel teks: STRING eksplisit + quote prefix untuk awalan = + - @
     * (defense-in-depth formula injection — dipakai semua sheet).
     *
     * @param array{int, int} $koordinat
     */
    private function tulisTeks(Worksheet $sheet, array $koordinat, string $nilai): void
    {
        $sheet->getCell($koordinat)->setValueExplicit($nilai, DataType::TYPE_STRING);
        if ($nilai !== '' && str_contains('=+-@', $nilai[0])) {
            $sheet->getStyle($koordinat)->setQuotePrefix(true);
        }
    }

    private function tulisTotal(Worksheet $sheet, int $baris, int $debit, int $kredit): void
    {
        $sheet->getCell([5, $baris])->setValueExplicit('TOTAL', DataType::TYPE_STRING);
        $sheet->getCell([6, $baris])->setValueExplicit((string) $debit, DataType::TYPE_NUMERIC);
        $sheet->getCell([7, $baris])->setValueExplicit((string) $kredit, DataType::TYPE_NUMERIC);
        $sheet->getStyle("E{$baris}:G{$baris}")->getFont()->setBold(true);
        $sheet->getStyle("F{$baris}:G{$baris}")->getNumberFormat()->setFormatCode('#,##0');
    }

    /** @param array<string, array{kode: string, nama: string, debit: int, kredit: int}> $rekap */
    private function tulisRekap(Spreadsheet $spreadsheet, array $rekap): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('Rekap Akun');

        foreach (['Kode Akun', 'Nama Akun', 'Debit', 'Kredit'] as $i => $judul) {
            $sheet->setCellValue([$i + 1, 1], $judul);
        }
        $sheet->getStyle('A1:D1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:D1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF15181E');
        foreach (['A' => 12, 'B' => 32, 'C' => 16, 'D' => 16] as $kol => $lebar) {
            $sheet->getColumnDimension($kol)->setWidth($lebar);
        }

        usort($rekap, fn (array $a, array $b): int => strcmp($a['kode'], $b['kode']));

        $baris = 2;
        foreach ($rekap as $r) {
            $this->tulisTeks($sheet, [1, $baris], $r['kode']);
            $this->tulisTeks($sheet, [2, $baris], $r['nama']);
            $sheet->getCell([3, $baris])->setValueExplicit((string) $r['debit'], DataType::TYPE_NUMERIC);
            $sheet->getCell([4, $baris])->setValueExplicit((string) $r['kredit'], DataType::TYPE_NUMERIC);
            $sheet->getStyle("C{$baris}:D{$baris}")->getNumberFormat()->setFormatCode('#,##0');
            $baris++;
        }

        $sheet->getCell([2, $baris])->setValueExplicit('TOTAL', DataType::TYPE_STRING);
        $sheet->getCell([3, $baris])->setValueExplicit((string) array_sum(array_column($rekap, 'debit')), DataType::TYPE_NUMERIC);
        $sheet->getCell([4, $baris])->setValueExplicit((string) array_sum(array_column($rekap, 'kredit')), DataType::TYPE_NUMERIC);
        $sheet->getStyle("B{$baris}:D{$baris}")->getFont()->setBold(true);
        $sheet->getStyle("C{$baris}:D{$baris}")->getNumberFormat()->setFormatCode('#,##0');
    }

    private function tulisInfo(Spreadsheet $spreadsheet, Carbon $dari, Carbon $sampai, int $jumlahTrx): void
    {
        $sheet = $spreadsheet->createSheet()->setTitle('Info');
        $baris = [
            ['Jurnal Akuntansi — Redline Komputer'],
            [''],
            ['Periode', $dari->format('Y-m-d').' s.d. '.$sampai->format('Y-m-d')],
            ['Dibuat', now()->format('Y-m-d H:i')],
            ['Jumlah transaksi', (string) $jumlahTrx],
            [''],
            ['Catatan:'],
            ['- Hanya transaksi berstatus Normal; transaksi Void/Refund dikecualikan TANPA jurnal balik.'],
            ['  PENTING: bila sebuah transaksi di-void SETELAH periodenya diekspor/diimpor akuntan,'],
            ['  ekspor ulang periode itu akan berbeda isi — koordinasikan koreksinya manual dengan akuntan.'],
            ['- HPP memakai SNAPSHOT harga modal saat transaksi (produk & part servis) sehingga tidak'],
            ['  berubah bila harga modal diedit belakangan. Transaksi lama (sebelum pembaruan snapshot)'],
            ['  memakai harga_modal produk saat ekspor; servis lama tanpa snapshot tercatat HPP 0.'],
            ['- Produk tanpa harga modal tercatat HPP 0 — isi via edit produk / ekspor-ubah-impor Excel.'],
            ['- Kode & nama akun dapat disesuaikan akuntan lewat config/redline.php bagian "akun".'],
            ['- Setiap blok transaksi seimbang: debit = kredit (jurnal umum double-entry).'],
        ];
        foreach ($baris as $r => $kolom) {
            foreach ($kolom as $k => $nilai) {
                $this->tulisTeks($sheet, [$k + 1, $r + 1], $nilai);
            }
        }
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(90);
        $sheet->getColumnDimension('B')->setWidth(30);
    }
}
