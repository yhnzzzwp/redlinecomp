<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\StatusService;
use App\Enums\TransaksiStatus;
use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Service;
use App\Models\Transaksi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiDashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = now()->startOfDay();

        $transaksiHariIni = Transaksi::where('status', TransaksiStatus::Normal)
            ->whereDate('created_at', $today)
            ->get();

        $pendapatanHariIni = (int) $transaksiHariIni->sum('total');
        $jumlahTransaksiHariIni = $transaksiHariIni->count();

        $transaksiBulanIni = Transaksi::where('status', TransaksiStatus::Normal)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        $pendapatanBulanIni = (int) $transaksiBulanIni->sum('total');
        $jumlahTransaksiBulanIni = $transaksiBulanIni->count();

        $statusServisCounts = [];
        foreach (StatusService::cases() as $st) {
            $statusServisCounts[$st->value] = Service::where('status', $st)->count();
        }

        $servisAktif = Service::whereNotIn('status', [StatusService::SudahDiambil])->count();
        $servisSiapAmbil = Service::where('status', StatusService::Selesai)->count();

        $promoAktif = Promo::where('aktif', true)->count();
        $totalProduk = Produk::count();
        $totalPegawai = Pegawai::where('masih_bekerja', true)->count();

        $transaksiTerbaru = Transaksi::with(['pegawai', 'promo'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Transaksi $t) => [
                'id'           => $t->id,
                'kode_nota'    => $t->kode_nota,
                'nama_pembeli' => $t->nama_pembeli,
                'total'        => (int) $t->total,
                'metode_bayar' => $t->metode_bayar->value,
                'status'       => $t->status->value,
                'kasir'        => $t->pegawai?->nama_pegawai,
                'waktu'        => $t->created_at?->format('Y-m-d H:i'),
            ]);

        $servisTerbaru = Service::with(['perangkat', 'teknisi'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Service $s) => [
                'id'            => $s->id,
                'nomor_resi'    => $s->nomor_resi,
                'customer'      => $s->perangkat?->nama_customer,
                'perangkat'     => $s->perangkat?->merk_model,
                'status'        => $s->status->value,
                'status_warna'  => $s->status->warna(),
                'total_biaya'   => $s->totalBiaya(),
                'tanggal_masuk' => $s->tanggal_masuk?->format('Y-m-d'),
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'penjualan' => [
                    'hari_ini' => [
                        'pendapatan' => $pendapatanHariIni,
                        'jumlah'     => $jumlahTransaksiHariIni,
                    ],
                    'bulan_ini' => [
                        'pendapatan' => $pendapatanBulanIni,
                        'jumlah'     => $jumlahTransaksiBulanIni,
                    ],
                ],
                'servis' => [
                    'aktif'         => $servisAktif,
                    'siap_diambil'  => $servisSiapAmbil,
                    'status_counts' => $statusServisCounts,
                ],
                'ringkasan' => [
                    'total_produk'  => $totalProduk,
                    'promo_aktif'   => $promoAktif,
                    'total_pegawai' => $totalPegawai,
                ],
                'transaksi_terbaru' => $transaksiTerbaru,
                'servis_terbaru'    => $servisTerbaru,
            ],
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $hari = min(365, max(7, (int) $request->input('hari', 30)));
        $startDate = now()->subDays($hari)->startOfDay();

        $transaksi = Transaksi::where('status', TransaksiStatus::Normal)
            ->where('created_at', '>=', $startDate)
            ->get();

        $totalPendapatan = (int) $transaksi->sum('total');
        $totalDiskon = (int) $transaksi->sum('diskon');
        $totalTransaksi = $transaksi->count();

        // Pendapatan per metode bayar
        $metodeBayarBreakdown = [];
        foreach ($transaksi->groupBy(fn (Transaksi $t) => $t->metode_bayar->value) as $metode => $group) {
            $metodeBayarBreakdown[] = [
                'metode'     => $metode,
                'total'      => (int) $group->sum('total'),
                'banyaknya'  => $group->count(),
                'persentase' => $totalPendapatan > 0 ? round(($group->sum('total') / $totalPendapatan) * 100, 1) : 0,
            ];
        }

        // Tren harian
        $trenHarian = [];
        for ($i = $hari - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayTrx = $transaksi->filter(fn ($t) => $t->created_at && $t->created_at->format('Y-m-d') === $date);
            $trenHarian[] = [
                'tanggal'    => $date,
                'pendapatan' => (int) $dayTrx->sum('total'),
                'transaksi'  => $dayTrx->count(),
            ];
        }

        // Status servis rekap
        $servisStats = [];
        foreach (StatusService::cases() as $st) {
            $servisStats[] = [
                'status' => $st->value,
                'warna'  => $st->warna(),
                'jumlah' => Service::where('status', $st)->count(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'periode_hari'     => $hari,
                'total_pendapatan' => $totalPendapatan,
                'total_diskon'     => $totalDiskon,
                'total_transaksi'  => $totalTransaksi,
                'metode_bayar'     => $metodeBayarBreakdown,
                'tren_harian'      => $trenHarian,
                'servis_stats'     => $servisStats,
            ],
        ]);
    }
}
