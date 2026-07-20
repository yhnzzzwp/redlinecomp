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
        $totalSales = (int) Transaksi::sum('total');
        $activeServices = Service::whereNotIn('status', [
            StatusService::Selesai->value, StatusService::SudahDiambil->value,
        ])->count();
        $totalProducts = Produk::count();

        $criticalStock = Produk::where('jumlah_produk', '<=', config('redline.stok_kritis'))
            ->orderBy('jumlah_produk')->limit(6)->get();

        $recent = Transaksi::with('pegawai')->latest()->limit(5)->get();

        $trend = collect(range(6, 0))->map(function ($d) {
            $day = Carbon::today()->subDays($d);
            return [
                'label' => $day->translatedFormat('D'),
                'total' => (int) Transaksi::whereDate('created_at', $day)->sum('total'),
            ];
        });

        return view('internal.dashboard', compact(
            'totalSales', 'activeServices', 'totalProducts', 'criticalStock', 'recent', 'trend'
        ));
    }
}
