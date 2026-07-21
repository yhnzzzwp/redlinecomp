<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Enums\TipeItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $dari = $request->filled('dari') ? \Carbon\Carbon::parse($request->dari)->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? \Carbon\Carbon::parse($request->sampai)->endOfDay() : now()->endOfMonth();

        $hariIni = now()->startOfDay();

        // Calculate only Normal transactions for revenue and profit
        // Wait, the instruction doesn't specify status filtering for analytics, but logically we should exclude Void/Refund.
        // Assuming we count all or just add date ranges as requested.
        $transaksiQuery = Transaksi::whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
        $hariIniQuery = Transaksi::where('created_at', '>=', $hariIni)->where('status', 'Normal');

        $pendapatanPeriode = $transaksiQuery->sum('total');
        $pendapatanHariIni = $hariIniQuery->sum('total');
        $transaksiPeriode = $transaksiQuery->count();

        // Profit calculation
        $profitData = ItemTransaksi::select(
            DB::raw('SUM(item_transaksi.subtotal - (COALESCE(produk.harga_modal, 0) * item_transaksi.jumlah)) as total_profit')
        )
            ->leftJoin('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->first();
        
        $profitPeriode = $profitData ? $profitData->total_profit : 0;

        // 7 days trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $total = Transaksi::whereDate('created_at', $date->format('Y-m-d'))->where('status', 'Normal')->sum('total');
            $trend[] = [
                'label' => $date->format('d M'),
                'total' => (int) $total,
            ];
        }

        // Top products
        $produkTerlaris = ItemTransaksi::select('nama_item', DB::raw('SUM(jumlah) as total_terjual'), DB::raw('SUM(subtotal) as total_pendapatan'))
            ->where('item_transaksi.tipe', TipeItem::Produk->value)
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('nama_item')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        // Revenue by category
        $pendapatanKategori = ItemTransaksi::select('item_transaksi.tipe', DB::raw('SUM(item_transaksi.subtotal) as total_pendapatan'))
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('item_transaksi.tipe')
            ->get();

        return view('internal.analytics.index', compact(
            'pendapatanPeriode',
            'profitPeriode',
            'pendapatanHariIni',
            'transaksiPeriode',
            'trend',
            'produkTerlaris',
            'pendapatanKategori',
            'dari',
            'sampai'
        ));
    }

    public function cetak(Request $request)
    {
        $dari = $request->filled('dari') ? \Carbon\Carbon::parse($request->dari)->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? \Carbon\Carbon::parse($request->sampai)->endOfDay() : now()->endOfMonth();
        
        $produkTerlaris = ItemTransaksi::select('nama_item', DB::raw('SUM(jumlah) as total_terjual'), DB::raw('SUM(subtotal) as total_pendapatan'))
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
            DB::raw('SUM(item_transaksi.subtotal) as total_pendapatan'),
            DB::raw('SUM(item_transaksi.subtotal - (COALESCE(produk.harga_modal, 0) * item_transaksi.jumlah)) as total_profit')
        )
            ->leftJoin('produk', 'item_transaksi.produk_id', '=', 'produk.id')
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->groupBy('item_transaksi.tipe')
            ->get();
            
        $totalPendapatan = $pendapatanKategori->sum('total_pendapatan');
        $totalProfit = $pendapatanKategori->sum('total_profit');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('internal.analytics.pdf', compact(
            'produkTerlaris', 'pendapatanKategori', 'totalPendapatan', 'totalProfit', 'dari', 'sampai'
        ));

        return $pdf->download('Laporan-Penjualan-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportCsv(Request $request)
    {
        $dari = $request->filled('dari') ? \Carbon\Carbon::parse($request->dari)->startOfDay() : now()->startOfMonth();
        $sampai = $request->filled('sampai') ? \Carbon\Carbon::parse($request->sampai)->endOfDay() : now()->endOfMonth();

        $items = ItemTransaksi::with(['transaksi.pegawai', 'produk'])
            ->whereHas('transaksi', function ($q) use ($dari, $sampai) {
                $q->whereBetween('created_at', [$dari, $sampai])->where('status', 'Normal');
            })
            ->get();

        $filename = 'Export-Penjualan-'.now()->format('Ymd-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Tanggal', 'No Transaksi', 'Kasir', 'Tipe', 'Item', 'Qty', 'Harga Satuan', 'Subtotal', 'HPP', 'Profit']);

            foreach ($items as $item) {
                $hpp = $item->tipe === TipeItem::Produk && $item->produk ? $item->produk->harga_modal : 0;
                $profit = $item->subtotal - ($hpp * $item->jumlah);

                fputcsv($file, [
                    $item->transaksi->created_at->format('Y-m-d H:i:s'),
                    $item->transaksi->nomor_transaksi,
                    $item->transaksi->pegawai->nama_pegawai,
                    $item->tipe->value,
                    $item->nama_item,
                    $item->jumlah,
                    $item->harga_satuan,
                    $item->subtotal,
                    $hpp,
                    $profit
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

