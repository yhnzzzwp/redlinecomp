<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Enums\StatusService;
use App\Http\Controllers\Controller;
use App\Models\Produk;
use App\Models\Service;
use App\Models\Transaksi;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $todaySales = (int) Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)
            ->whereDate('created_at', Carbon::today())
            ->sum('total');
        $todayCount = Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $activeServices = Service::whereNotIn('status', [
            StatusService::Selesai->value, StatusService::SudahDiambil->value,
        ])->count();
        $totalProducts = Produk::count();

        $criticalStock = Produk::with('kategori')
            ->where('jumlah_produk', '<=', config('redline.stok_kritis', 5))
            ->orderBy('jumlah_produk')
            ->limit(6)
            ->get();

        $recent = Transaksi::with('pegawai')->latest()->limit(5)->get();

        $trend = collect(range(6, 0))->map(function ($d) {
            $day = Carbon::today()->subDays($d);
            return [
                'label' => $day->translatedFormat('d M'),
                'total' => (int) Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)
                    ->whereDate('created_at', $day)
                    ->sum('total'),
            ];
        });

        return view('internal.dashboard', compact(
            'totalSales',
            'todaySales',
            'todayCount',
            'activeServices',
            'totalProducts',
            'criticalStock',
            'recent',
            'trend'
        ));
    }
}
