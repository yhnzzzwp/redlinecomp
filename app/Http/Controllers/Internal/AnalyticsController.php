<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\TipeItem;
use App\Http\Controllers\Controller;
use App\Models\ItemTransaksi;
use App\Models\Produk;
use App\Models\Transaksi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AnalyticsController extends Controller
{
    public function __construct() {}

    public function index(Request $request): View
    {
        $dari = $request->filled('dari') ? \Carbon\Carbon::parse($request->string('dari')->toString())->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? \Carbon\Carbon::parse($request->string('sampai')->toString())->endOfDay() : now()->endOfMonth();

        $hariIni = now()->startOfDay();

        $transaksiQuery = Transaksi::whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
        $hariIniQuery = Transaksi::where('created_at', '>=', $hariIni)->where('status', 'Normal');

        $jumlahPeriode = $transaksiQuery->count();
        $jumlahHariIni = $hariIniQuery->count();

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $total = Transaksi::whereDate('created_at', $date->format('Y-m-d'))->where('status', 'Normal')->count();
            $trend[] = [
                'label' => $date->format('d M'),
                'total' => (int) $total,
            ];
        }

        $produkTerlaris = ItemTransaksi::select('nama_item', DB::raw('SUM(jumlah) as total_terjual'))
            ->where('item_transaksi.tipe', TipeItem::Produk->value)
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('nama_item')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        $pendapatanKategori = ItemTransaksi::select('item_transaksi.tipe', DB::raw('COUNT(*) as jumlah_terjual'))
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('item_transaksi.tipe')
            ->get();

        return view('internal.analytics.index', compact(
            'jumlahPeriode',
            'jumlahHariIni',
            'trend',
            'produkTerlaris',
            'pendapatanKategori',
            'dari',
            'sampai'
        ));
    }

    public function cetak(Request $request): mixed
    {
        $dari = $request->filled('dari') ? \Carbon\Carbon::parse($request->string('dari')->toString())->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? \Carbon\Carbon::parse($request->string('sampai')->toString())->endOfDay() : now()->endOfMonth();

        $produkTerlaris = ItemTransaksi::select('nama_item', DB::raw('SUM(jumlah) as total_terjual'))
            ->where('item_transaksi.tipe', TipeItem::Produk->value)
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('nama_item')
            ->orderByDesc('total_terjual')
            ->limit(10)
            ->get();

        $pendapatanKategori = ItemTransaksi::select(
            'item_transaksi.tipe',
            DB::raw('COUNT(*) as jumlah_terjual')
        )
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('item_transaksi.tipe')
            ->get();

        $totalPendapatan = $pendapatanKategori->sum('jumlah_terjual');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('internal.analytics.pdf', compact(
            'produkTerlaris', 'pendapatanKategori', 'totalPendapatan', 'dari', 'sampai'
        ));

        return $pdf->download('Laporan-Penjualan-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $dari = $request->filled('dari') ? \Carbon\Carbon::parse($request->string('dari')->toString())->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? \Carbon\Carbon::parse($request->string('sampai')->toString())->endOfDay() : now()->endOfMonth();

        $items = ItemTransaksi::with(['transaksi.pegawai', 'produk'])
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->get();

        $filename = 'Export-Penjualan-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($items): void {
            $file = fopen('php://output', 'w');
            if ($file !== false) {
                fputcsv($file, ['Tanggal', 'No Transaksi', 'Kasir', 'Tipe', 'Item', 'Qty', 'Harga Satuan', 'Subtotal']);

                foreach ($items as $item) {

                    $trx = $item->transaksi;
                    $tanggal = $trx && $trx->created_at ? $trx->created_at->format('Y-m-d H:i:s') : '-';
                    $kodeNota = $trx ? $trx->kode_nota : '-';
                    $pegawai = $trx?->pegawai;
                    $kasir = $pegawai instanceof \App\Models\Pegawai ? $pegawai->nama_pegawai : '-';
                    $tipeVal = $item->tipe->value;

                    $row = [
                        $tanggal,
                        $kodeNota,
                        $kasir,
                        $tipeVal,
                        $item->nama_item,
                        $item->jumlah,
                        $item->harga,
                        $item->subtotal,
                    ];

                    fputcsv($file, $row);
                }
                fclose($file);
            }
        };

        return response()->stream($callback, 200, $headers);
    }
}
