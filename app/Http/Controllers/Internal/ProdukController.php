<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProdukController extends Controller
{
    public function __construct(private readonly ProductService $service) {}

    public function index(Request $request): View
    {
        $query = Produk::query()->with('kategori')->latest();

        if ($request->filled('cari')) {
            $cari = (string) $request->string('cari');
            $query->where(function ($q) use ($cari): void {
                $q->where('nama_produk', 'like', "%{$cari}%")
                    ->orWhere('sku', 'like', "%{$cari}%");
            });
        }

        return view('internal.produk.index', [
            'produk' => $query->paginate(10)->withQueryString(),
            'total' => Produk::query()->count(),
            'cari' => $request->string('cari')->toString(),
            'lowStockCount' => Produk::query()->where('jumlah_produk', '<=', config('redline.stok_kritis', 5))->count(),
        ]);
    }

    public function templateCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Format-Import-Produk.csv"',
        ];

        $callback = function (): void {
            $file = fopen('php://output', 'w');
            if ($file !== false) {
                // UTF-8 BOM
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['nama_produk', 'sku', 'kategori', 'harga', 'harga_modal', 'jumlah_produk', 'deskripsi']);
                fputcsv($file, ['Processor Intel Core i5-13400F', 'RL-PROC-001', 'Processor', '3100000', '2850000', '10', 'LGA1700 Gen 13']);
                fputcsv($file, ['RAM Corsair Vengeance 16GB DDR4', 'RL-RAM-002', 'RAM', '950000', '820000', '25', 'PC DDR4 3200MHz']);
                fputcsv($file, ['SSD Samsung 980 NVMe 500GB', 'RL-SSD-003', 'Storage', '850000', '750000', '15', 'M.2 NVMe PCIe 3.0']);
                fclose($file);
            }
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file_csv' => 'required|file|mimes:csv,txt|max:4096',
        ], [
            'file_csv.required' => 'File CSV wajib diunggah.',
            'file_csv.max' => 'Ukuran file CSV tidak boleh melebihi 4MB.',
        ]);

        $file = $request->file('file_csv');
        if (! $file || ! $file->isValid()) {
            return back()->withErrors(['file_csv' => 'File tidak valid.']);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['file_csv' => 'Gagal membaca file CSV.']);
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return back()->withErrors(['file_csv' => 'File CSV kosong.']);
        }

        // Remove BOM if present
        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $header = str_getcsv(trim($firstLine), $delimiter);
        $headerMap = [];
        foreach ($header as $idx => $colName) {
            $normalized = strtolower(trim(str_replace(['_', '-'], '', (string) $colName)));
            $headerMap[$normalized] = $idx;
        }

        $findCol = function(array $headerMap, array $aliases, int $defaultIdx): int {
            foreach ($aliases as $alias) {
                $clean = strtolower(trim(str_replace(['_', '-'], '', $alias)));
                if (isset($headerMap[$clean])) {
                    return $headerMap[$clean];
                }
            }
            return $defaultIdx;
        };

        $idxNama = $findCol($headerMap, ['nama_produk', 'nama', 'product_name', 'product', 'nama_barang', 'barang', 'item', 'title', 'nama_item'], 0);
        $idxSku = $findCol($headerMap, ['sku', 'kode', 'code', 'barcode', 'kode_produk', 'kode_barang', 'sku_code', 'part_number'], 1);
        $idxKategori = $findCol($headerMap, ['kategori', 'category', 'cat', 'jenis', 'kategori_produk', 'kelompok'], 2);
        $idxHarga = $findCol($headerMap, ['harga', 'harga_jual', 'price', 'sell_price', 'harga_produk', 'nominal', 'price_idr'], 3);
        $idxHargaModal = $findCol($headerMap, ['harga_modal', 'modal', 'cost', 'hpp', 'buy_price', 'harga_beli', 'cost_price'], 4);
        $idxJumlah = $findCol($headerMap, ['jumlah_produk', 'stok', 'stock', 'jumlah', 'qty', 'quantity', 'sisa', 'sisa_stok'], 5);
        $idxDeskripsi = $findCol($headerMap, ['deskripsi', 'deskripsi_produk', 'description', 'ket', 'keterangan', 'detail', 'notes'], 6);

        $imported = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 2048, $delimiter)) !== false) {
                if (count($row) === 1 && empty($row[0])) {
                    continue;
                }

                $nama = isset($row[$idxNama]) ? trim((string) $row[$idxNama]) : '';
                if ($nama === '') {
                    continue;
                }

                $skuUser = isset($row[$idxSku]) ? trim((string) $row[$idxSku]) : '';
                $katNama = isset($row[$idxKategori]) ? trim((string) $row[$idxKategori]) : '';
                $harga = isset($row[$idxHarga]) ? (int) preg_replace('/[^0-9]/', '', (string) $row[$idxHarga]) : 0;
                $harga = min(10_000_000_000, max(0, $harga));

                $hargaModal = isset($row[$idxHargaModal]) ? (int) preg_replace('/[^0-9]/', '', (string) $row[$idxHargaModal]) : 0;
                $hargaModal = min(10_000_000_000, max(0, $hargaModal));

                $jumlah = isset($row[$idxJumlah]) ? (int) preg_replace('/[^0-9]/', '', (string) $row[$idxJumlah]) : 0;
                $jumlah = min(1_000_000, max(0, $jumlah));
                $deskripsi = isset($row[$idxDeskripsi]) ? trim((string) $row[$idxDeskripsi]) : null;

                $kategoriId = null;
                if ($katNama !== '') {
                    $kategoriObj = KategoriProduk::query()->firstOrCreate(['nama_kategori' => $katNama]);
                    $kategoriId = $kategoriObj->id;
                }

                $existing = null;
                if ($skuUser !== '') {
                    $existing = Produk::query()->where('sku', $skuUser)->first();
                } else {
                    $existing = Produk::query()->where('nama_produk', $nama)->first();
                }

                $sku = $skuUser !== '' ? $skuUser : ($existing?->sku ?? ('RL-PRD-' . strtoupper(Str::random(6))));
                if ($existing) {
                    $existing->update([
                        'nama_produk' => $nama,
                        'kategori_id' => $kategoriId ?? $existing->kategori_id,
                        'harga' => $harga > 0 ? $harga : $existing->harga,
                        'harga_modal' => $hargaModal > 0 ? $hargaModal : $existing->harga_modal,
                        'jumlah_produk' => $jumlah > 0 ? $jumlah : $existing->jumlah_produk,
                        'deskripsi_produk' => $deskripsi ?? $existing->deskripsi_produk,
                    ]);
                    $updated++;
                } else {
                    Produk::query()->create([
                        'nama_produk' => $nama,
                        'sku' => $sku,
                        'kategori_id' => $kategoriId,
                        'harga' => $harga,
                        'harga_modal' => $hargaModal,
                        'jumlah_produk' => $jumlah,
                        'deskripsi_produk' => $deskripsi,
                        'show_katalog' => true,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            report($e);
            return back()->withErrors(['file_csv' => 'Terjadi kesalahan saat mengimpor file CSV. Silakan periksa format file dan coba lagi.']);
        }

        fclose($handle);

        $totalProses = $imported + $updated;
        if ($totalProses === 0) {
            return back()->withErrors(['file_csv' => 'Tidak ada data produk yang valid dalam file CSV.']);
        }

        return redirect()->route('produk.index')
            ->with('success', "Berhasil memproses file CSV: {$imported} produk baru ditambahkan, {$updated} produk diperbarui.");
    }

    public function create(): View
    {
        return view('internal.produk.form', [
            'produk' => new Produk(),
            'kategori' => KategoriProduk::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(StoreProdukRequest $request): RedirectResponse
    {
        $this->service->create($request->safe()->except('foto'), $request->file('foto'));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Produk $produk): View
    {
        return view('internal.produk.form', [
            'produk' => $produk,
            'kategori' => KategoriProduk::query()->orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(UpdateProdukRequest $request, Produk $produk): RedirectResponse
    {
        $this->service->update($produk, $request->safe()->except('foto'), $request->file('foto'));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $this->service->delete($produk);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }
}
