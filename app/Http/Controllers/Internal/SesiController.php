<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Manajemen sesi aktif — pegawai melihat perangkat mana saja yang sedang
 * login dengan akunnya dan bisa mengeluarkan perangkat lain (mis. lupa
 * logout di HP). Bekerja di atas SESSION_DRIVER=database: menghapus baris
 * sesi membuat perangkat itu ter-logout pada request berikutnya.
 *
 * Hanya sesi MILIK SENDIRI yang terlihat/tersentuh; sesi yang sedang
 * dipakai tidak bisa dikeluarkan dari sini (pakai tombol Logout).
 */
final class SesiController extends Controller
{
    public function index(Request $request): View
    {
        $daftarSesi = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            // Baris kedaluwarsa menunggu GC — jangan ditampilkan.
            ->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->getTimestamp())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        return view('internal.sesi', [
            'daftarSesi' => $daftarSesi,
            'sesiSekarang' => $request->session()->getId(),
        ]);
    }

    public function keluarkan(Request $request, string $id): RedirectResponse
    {
        if ($id === $request->session()->getId()) {
            return back()->with('error', 'Sesi yang sedang dipakai tidak bisa dikeluarkan — gunakan tombol Logout.');
        }

        $dihapus = DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $request->user()->id) // hanya sesi milik sendiri
            ->delete();

        return $dihapus > 0
            ? back()->with('success', 'Perangkat dikeluarkan — sesinya berakhir pada request berikutnya.')
            : back()->with('error', 'Sesi tidak ditemukan (mungkin sudah berakhir).');
    }

    public function keluarkanLain(Request $request): RedirectResponse
    {
        $jumlah = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', $jumlah > 0
            ? "{$jumlah} perangkat lain dikeluarkan."
            : 'Tidak ada perangkat lain yang sedang login.');
    }
}
