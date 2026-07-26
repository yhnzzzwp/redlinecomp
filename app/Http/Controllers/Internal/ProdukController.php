<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Exceptions\ImporProdukException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProdukRequest;
use App\Http\Requests\UpdateProdukRequest;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Services\ProductService;
use App\Services\ProdukExcelService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProdukController extends Controller
{
    public function __construct(
        private readonly ProductService $service,
        private readonly ProdukExcelService $excel,
    ) {}

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

    public function template(): StreamedResponse
    {
        return $this->unduhXlsx($this->excel->template(), 'Format-Import-Produk.xlsx');
    }

    public function export(): StreamedResponse
    {
        return $this->unduhXlsx($this->excel->ekspor(), 'Produk-Redline-' . now()->format('Ymd-Hi') . '.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls|max:5120',
        ], [
            'file_excel.required' => 'File Excel wajib diunggah.',
            'file_excel.mimes' => 'Format harus Excel (.xlsx / .xls). Format CSV sudah tidak didukung — gunakan tombol "Unduh Template".',
            'file_excel.max' => 'Ukuran file tidak boleh melebihi 5MB.',
        ]);

        $file = $request->file('file_excel');
        if (! $file || ! $file->isValid()) {
            return back()->withErrors(['file_excel' => 'File tidak valid.']);
        }

        try {
            $hasil = $this->excel->import($file);
        } catch (ImporProdukException $e) {
            return back()
                ->withErrors(['file_excel' => $e->getMessage()])
                ->with('import_baris_gagal', $e->barisGagal);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['file_excel' => 'File tidak dapat dibaca. Pastikan berformat Excel yang benar.']);
        }

        return redirect()->route('produk.index')->with('success', $hasil->ringkasan());
    }

    private function unduhXlsx(Spreadsheet $spreadsheet, string $nama): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $nama, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
        $this->service->create($request->validated());

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
        $this->service->update($produk, $request->validated());

        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $this->service->delete($produk);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }
}
