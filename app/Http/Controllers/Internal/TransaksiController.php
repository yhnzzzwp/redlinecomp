<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\TipeItem;
use App\Enums\TransaksiStatus;
use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TransaksiController extends Controller
{
    public function index(Request $request): View
    {
        $query = Transaksi::query()->with(['items', 'pegawai'])->latest();

        if ($request->filled('cari')) {
            $query->where('kode_nota', 'like', '%' . $request->string('cari')->toString() . '%');
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->string('tanggal')->toString());
        }

        if ($request->filled('jenis')) {
            $jenis = $request->string('jenis')->toString();
            $query->whereHas('items', function ($q) use ($jenis) {
                $q->where('tipe', $jenis);
            });
        }

        return view('internal.transaksi.index', [
            'transaksi' => $query->paginate(15)->withQueryString(),
            'cari' => $request->string('cari')->toString(),
            'tanggal' => $request->string('tanggal')->toString(),
            'jenis' => $request->string('jenis')->toString(),
        ]);
    }

    public function void(Transaksi $transaksi): RedirectResponse
    {
        if ($transaksi->status !== TransaksiStatus::Normal) {
            return back()->withErrors(['message' => 'Hanya transaksi Normal yang bisa dibatalkan (Void).']);
        }

        DB::transaction(function () use ($transaksi) {
            $transaksi->update(['status' => TransaksiStatus::Void->value]);

            foreach ($transaksi->items as $item) {
                /** @var \App\Models\ItemTransaksi $item */
                if ($item->tipe === TipeItem::Produk && $item->produk_id) {
                    $produk = Produk::find($item->produk_id);
                    if ($produk) {
                        $produk->increment('jumlah_produk', $item->jumlah);
                    }
                }
            }
        });

        return back()->with('success', "Transaksi {$transaksi->kode_nota} berhasil dibatalkan (Void) dan stok dikembalikan.");
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $query = Transaksi::query()->with(['items', 'pegawai'])->latest();

        if ($request->filled('cari')) {
            $query->where('kode_nota', 'like', '%' . $request->string('cari')->toString() . '%');
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->string('tanggal')->toString());
        }

        if ($request->filled('jenis')) {
            $jenis = $request->string('jenis')->toString();
            $query->whereHas('items', function ($q) use ($jenis) {
                $q->where('tipe', $jenis);
            });
        }

        $transaksis = $query->get();

        $filename = 'Export-Daftar-Transaksi-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transaksis): void {
            $file = fopen('php://output', 'w');
            if ($file !== false) {
                fputcsv($file, ['Waktu', 'No Transaksi', 'Kasir', 'Total', 'Status', 'Metode Bayar']);

                foreach ($transaksis as $t) {
                    /** @var Transaksi $t */
                    $created = $t->created_at ? $t->created_at->format('Y-m-d H:i:s') : '-';
                    $statusVal = $t->status->value;
                    $kasir = $t->pegawai instanceof \App\Models\Pegawai ? $t->pegawai->nama_pegawai : '-';

                    /** @var array<int, string|int|float|null> $row */
                    $row = [
                        $created,
                        $t->kode_nota,
                        $kasir,
                        $t->total,
                        $statusVal,
                        $t->metode_bayar ?? '-',
                    ];

                    fputcsv($file, $row);
                }
                fclose($file);
            }
        };

        return response()->stream($callback, 200, $headers);
    }
}
