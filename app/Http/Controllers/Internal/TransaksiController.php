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
}
