<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
    public function void(Transaksi $transaksi): \Illuminate\Http\RedirectResponse
    {
        if ($transaksi->status !== \App\Enums\TransaksiStatus::Normal->value) {
            return back()->withErrors(['message' => 'Hanya transaksi Normal yang bisa dibatalkan (Void).']);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaksi) {
            $transaksi->update(['status' => \App\Enums\TransaksiStatus::Void->value]);

            foreach ($transaksi->items as $item) {
                if ($item->tipe === \App\Enums\TipeItem::Produk && $item->produk_id) {
                    $produk = \App\Models\Produk::find($item->produk_id);
                    if ($produk) {
                        $produk->increment('jumlah_produk', $item->jumlah);
                    }
                }
            }
        });

        return back()->with('success', "Transaksi {$transaksi->nomor_transaksi} berhasil dibatalkan (Void) dan stok dikembalikan.");
    }

    public function exportCsv(Request $request)
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

        $filename = 'Export-Daftar-Transaksi-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transaksis) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Waktu', 'No Transaksi', 'Kasir', 'Total', 'Status', 'Metode Bayar']);

            foreach ($transaksis as $t) {
                fputcsv($file, [
                    $t->created_at->format('Y-m-d H:i:s'),
                    $t->nomor_transaksi,
                    $t->pegawai->nama_pegawai ?? '-',
                    $t->total,
                    $t->status,
                    $t->metode_bayar->value ?? '-'
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
