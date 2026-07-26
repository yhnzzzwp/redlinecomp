<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 2FA TOTP untuk Owner di portal admin: kelola (aktif/nonaktif) dan
 * tantangan kode saat login. Kode pemulihan sekali-pakai disimpan sebagai hash.
 */
final class TotpController extends Controller
{
    /* ---------------- Kelola (auth + owner) ---------------- */

    public function kelola(Request $request)
    {
        /** @var Pegawai $user */
        $user = $request->user();

        $secretBaru = null;
        $otpauth = null;
        if (! $user->totpAktif()) {
            $secretBaru = (string) $request->session()->get('totp_setup_secret', '');
            if ($secretBaru === '') {
                $secretBaru = Totp::buatSecret();
                $request->session()->put('totp_setup_secret', $secretBaru);
            }
            $otpauth = Totp::otpauthUri($user, $secretBaru);
        }

        return view('internal.keamanan', [
            'aktif' => $user->totpAktif(),
            'secret' => $secretBaru,
            'otpauth' => $otpauth,
            'kodePemulihanBaru' => $request->session()->get('totp_recovery_baru'),
        ]);
    }

    public function aktifkan(Request $request): RedirectResponse
    {
        $request->validate(['kode' => ['required', 'digits:6']]);

        /** @var Pegawai $user */
        $user = $request->user();
        $secret = (string) $request->session()->get('totp_setup_secret', '');

        if ($secret === '' || ! Totp::verifikasi($secret, $request->string('kode')->toString())) {
            throw ValidationException::withMessages(['kode' => 'Kode tidak cocok. Pastikan jam perangkat akurat, lalu coba lagi.']);
        }

        // 6 kode pemulihan sekali-pakai: tampil sekali, tersimpan sebagai hash.
        $polos = collect(range(1, 6))
            ->map(fn (): string => Str::upper(Str::random(4)) . '-' . Str::upper(Str::random(4)))
            ->all();

        $user->forceFill([
            'totp_secret' => $secret,
            'totp_recovery' => array_map(fn (string $k): string => Hash::make($k), $polos),
        ])->save();

        $request->session()->forget('totp_setup_secret');

        return redirect()->route('keamanan')
            ->with('totp_recovery_baru', $polos)
            ->with('success', '2FA aktif. Simpan kode pemulihan di tempat aman — hanya ditampilkan sekali.');
    }

    public function nonaktifkan(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill(['totp_secret' => null, 'totp_recovery' => null])->save();

        return redirect()->route('keamanan')->with('success', '2FA dinonaktifkan.');
    }

    /* ---------------- Tantangan saat login (guest) ---------------- */

    public function tantangan(Request $request)
    {
        if (! $request->session()->has('totp.id')) {
            return redirect()->route('login');
        }

        return view('auth.totp');
    }

    public function verifikasi(Request $request): RedirectResponse
    {
        $request->validate(['kode' => ['required', 'string', 'max:16']]);

        $id = $request->session()->get('totp.id');
        $pegawai = $id !== null ? Pegawai::query()->find($id) : null;
        if (! $pegawai instanceof Pegawai || ! $pegawai->totpAktif()) {
            return redirect()->route('login');
        }

        $kode = $request->string('kode')->toString();

        if (! $this->kodeDiterima($pegawai, $kode)) {
            throw ValidationException::withMessages(['kode' => 'Kode salah atau sudah terpakai.']);
        }

        $remember = (bool) $request->session()->pull('totp.remember', false);
        $request->session()->forget('totp.id');

        Auth::loginUsingId($pegawai->id, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /** Terima kode TOTP 6 digit ATAU kode pemulihan (dikonsumsi sekali). */
    private function kodeDiterima(Pegawai $pegawai, string $kode): bool
    {
        if (preg_match('/^\d{6}$/', $kode) === 1 && Totp::verifikasi((string) $pegawai->totp_secret, $kode)) {
            return true;
        }

        $sisa = collect($pegawai->totp_recovery ?? [])
            ->reject(fn (string $hash): bool => Hash::check(strtoupper(trim($kode)), $hash));

        if ($sisa->count() === count($pegawai->totp_recovery ?? [])) {
            return false;
        }

        $pegawai->forceFill(['totp_recovery' => $sisa->values()->all()])->save();

        return true;
    }
}
