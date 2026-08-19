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
        $totalSales = (int) Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)->count();
        $todaySales = (int) Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)
            ->whereDate('created_at', Carbon::today())
            ->count();
        $todayCount = Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $activeServices = Service::whereNotIn('status', [
            StatusService::Selesai->value, StatusService::SudahDiambil->value,
        ])->count();
        $totalProducts = Produk::count();

        $recent = Transaksi::with('pegawai')->latest()->limit(5)->get();

        $trend = collect(range(6, 0))->map(function ($d) {
            $day = Carbon::today()->subDays($d);
            return [
                'label' => $day->translatedFormat('d M'),
                'total' => (int) Transaksi::where('status', \App\Enums\TransaksiStatus::Normal)
                    ->whereDate('created_at', $day)
                    ->count(),
            ];
        });

        return view('internal.dashboard', compact(
            'totalSales',
            'todaySales',
            'todayCount',
            'activeServices',
            'totalProducts',
            'recent',
            'trend'
        ));
    }
}
