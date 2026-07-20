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
        $bulanIni = now()->startOfMonth();
        $hariIni = now()->startOfDay();

        $pendapatanBulanIni = Transaksi::where('created_at', '>=', $bulanIni)->sum('total');
        $pendapatanHariIni = Transaksi::where('created_at', '>=', $hariIni)->sum('total');
        
        $transaksiBulanIni = Transaksi::where('created_at', '>=', $bulanIni)->count();

        // 7 days trend
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $total = Transaksi::whereDate('created_at', $date->format('Y-m-d'))->sum('total');
            $trend[] = [
                'label' => $date->format('d M'),
                'total' => (int) $total,
            ];
        }

        // Top products this month
        $produkTerlaris = ItemTransaksi::select('nama_item', DB::raw('SUM(jumlah) as total_terjual'), DB::raw('SUM(subtotal) as total_pendapatan'))
            ->where('tipe', TipeItem::Produk->value)
            ->whereHas('transaksi', function ($q) use ($bulanIni) {
                $q->where('created_at', '>=', $bulanIni);
            })
            ->groupBy('nama_item')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        // Revenue by category (rough estimation from item name/type)
        $pendapatanKategori = ItemTransaksi::select('tipe', DB::raw('SUM(subtotal) as total_pendapatan'))
            ->whereHas('transaksi', function ($q) use ($bulanIni) {
                $q->where('created_at', '>=', $bulanIni);
            })
            ->groupBy('tipe')
            ->get();

        return view('internal.analytics.index', compact(
            'pendapatanBulanIni',
            'pendapatanHariIni',
            'transaksiBulanIni',
            'trend',
            'produkTerlaris',
            'pendapatanKategori'
        ));
    }

    public function cetak(Request $request)
    {
        $bulanIni = now()->startOfMonth();
        
        $produkTerlaris = ItemTransaksi::select('nama_item', DB::raw('SUM(jumlah) as total_terjual'), DB::raw('SUM(subtotal) as total_pendapatan'))
            ->where('tipe', TipeItem::Produk->value)
            ->whereHas('transaksi', function ($q) use ($bulanIni) {
                $q->where('created_at', '>=', $bulanIni);
            })
            ->groupBy('nama_item')
            ->orderByDesc('total_terjual')
            ->limit(10)
            ->get();

        $pendapatanKategori = ItemTransaksi::select('tipe', DB::raw('SUM(subtotal) as total_pendapatan'))
            ->whereHas('transaksi', function ($q) use ($bulanIni) {
                $q->where('created_at', '>=', $bulanIni);
            })
            ->groupBy('tipe')
            ->get();
            
        $totalPendapatan = $pendapatanKategori->sum('total_pendapatan');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('internal.analytics.pdf', compact(
            'produkTerlaris', 'pendapatanKategori', 'totalPendapatan'
        ));

        return $pdf->download('Laporan-Penjualan-'.now()->format('Y-m-d').'.pdf');
    }
}

