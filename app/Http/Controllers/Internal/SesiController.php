<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        if ($dihapus === 0) {
            return back()->with('error', 'Sesi tidak ditemukan (mungkin sudah berakhir).');
        }

        $this->putusIngatPerangkat($request);

        return back()->with('success', 'Perangkat dikeluarkan — sesinya berakhir seketika.');
    }

    public function keluarkanLain(Request $request): RedirectResponse
    {
        $jumlah = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        if ($jumlah === 0) {
            return back()->with('success', 'Tidak ada perangkat lain yang sedang login.');
        }

        $this->putusIngatPerangkat($request);

        return back()->with('success', "{$jumlah} perangkat lain dikeluarkan.");
    }

    /**
     * Owner mengeluarkan SELURUH sesi pegawai lain dari halaman Akun Pegawai —
     * mis. HP karyawan hilang atau akun dipakai orang lain, tanpa harus
     * menonaktifkan akunnya.
     *
     * Token "Ingat perangkat" pegawai itu selalu dirotasi, juga ketika tak ada
     * baris sesi tersisa: cookie recaller hidup lebih lama daripada sesi, jadi
     * tanpa rotasi perangkatnya bisa login sendiri lagi setelah sesi kedaluwarsa.
     */
    public function keluarkanPegawai(Request $request, \App\Models\Pegawai $pegawai): RedirectResponse
    {
        if ($pegawai->id === $request->user()->id) {
            return back()->with('error', 'Untuk perangkat Anda sendiri, gunakan halaman Sesi Aktif.');
        }

        $jumlah = DB::table('sessions')->where('user_id', $pegawai->id)->delete();

        $pegawai->setRememberToken(Str::random(60));
        $pegawai->save();

        return back()->with('success', $jumlah > 0
            ? "{$jumlah} perangkat {$pegawai->nama_pegawai} dikeluarkan."
            : "Tidak ada sesi aktif milik {$pegawai->nama_pegawai}; cookie \"Ingat perangkat\"-nya tetap dicabut.");
    }

    /**
     * Menghapus baris sesi saja TIDAK cukup: perangkat yang login dengan
     * "Ingat perangkat" menyimpan cookie recaller dan akan membuat sesi baru
     * pada request berikutnya. Rotasi remember_token mematikan cookie itu.
     *
     * Konsekuensi (disebut di UI): token bersifat per-akun, jadi status
     * "diingat" hilang di SEMUA perangkat. Perangkat yang sedang dipakai tetap
     * login (sesinya utuh) dan cookie-nya diterbitkan ulang bila tadinya ada.
     */
    private function putusIngatPerangkat(Request $request): void
    {
        $pegawai = $request->user();
        $tadinyaDiingat = $request->cookies->has(Auth::guard()->getRecallerName());

        $pegawai->setRememberToken(Str::random(60));
        $pegawai->save();

        if ($tadinyaDiingat) {
            Auth::login($pegawai, true); // terbitkan ulang recaller dengan token baru
        }
    }
}
