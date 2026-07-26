<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Support\Portal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['portal' => Portal::fromRequest($request)]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $portal = Portal::fromRequest($request);

        // Kunci throttle memuat portal supaya percobaan di satu portal
        // tidak bisa dipakai mengunci akun di portal lain.
        $key = $portal->value.'|'.Str::lower($data['login']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'login' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        $field = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [
            $field => $data['login'],
            'password' => $data['password'],
            'masih_bekerja' => true,
            // Role wajib cocok dengan portal (Owner ↔ admin, Karyawan ↔ karyawan).
            // Ketidakcocokan menghasilkan pesan galat generik yang sama, jadi
            // keberadaan akun di portal lain tidak bocor.
            'role' => $portal->expectedRole()?->value,
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            Log::info('Login gagal', ['portal' => $portal->value, 'login' => $data['login'], 'ip' => $request->ip()]);
            throw ValidationException::withMessages([
                'login' => 'Username atau password salah.',
            ]);
        }

        RateLimiter::clear($key);

        // Owner dengan 2FA aktif: password saja belum cukup — tahan login,
        // minta kode TOTP dulu (identitas disimpan sementara di sesi).
        $user = Auth::user();
        if ($portal === Portal::Admin && $user instanceof \App\Models\Pegawai && $user->totpAktif()) {
            Auth::logout();
            $request->session()->put('totp.id', $user->id);
            $request->session()->put('totp.remember', $request->boolean('remember'));

            return redirect()->route('totp.tantangan');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
