<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ApiAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'portal'   => ['nullable', 'string', 'in:admin,karyawan'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Username dan password wajib diisi.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $login = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        $portal = $request->input('portal');

        $pegawai = Pegawai::query()
            ->where(function ($q) use ($login) {
                $q->where('username', $login)
                  ->orWhere('email', $login);
            })
            ->where('masih_bekerja', true)
            ->first();

        if (! $pegawai || ! Hash::check($password, $pegawai->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Username atau password salah.',
            ], 401);
        }

        // Validate portal role access if specified
        if ($portal === 'admin' && ! $pegawai->isOwner()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Akun Anda tidak memiliki hak akses Owner/Admin Console.',
            ], 403);
        }

        // Create persistent API token
        $deviceName = (string) $request->input('device_name', $request->userAgent() ?? 'API Client');
        $token = $pegawai->createApiToken($deviceName);

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data'   => [
                'token' => $token,
                'user'  => [
                    'id'            => $pegawai->id,
                    'nama_pegawai'  => $pegawai->nama_pegawai,
                    'username'      => $pegawai->username,
                    'email'         => $pegawai->email,
                    'role'          => $pegawai->role->value ?? (string) $pegawai->role,
                    'nomor_hp'      => $pegawai->nomor_hp,
                    'alamat_pegawai'=> $pegawai->alamat_pegawai,
                    'is_owner'      => $pegawai->isOwner(),
                ],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Pegawai|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'            => $user->id,
                'nama_pegawai'  => $user->nama_pegawai,
                'username'      => $user->username,
                'email'         => $user->email,
                'role'          => $user->role->value ?? (string) $user->role,
                'nomor_hp'      => $user->nomor_hp,
                'alamat_pegawai'=> $user->alamat_pegawai,
                'tanggal_masuk' => $user->tanggal_masuk?->format('Y-m-d'),
                'is_owner'      => $user->isOwner(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $header = $request->header('Authorization', '');
        $token = null;

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        }

        if ($token) {
            ApiToken::where('token', hash('sha256', $token))->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil. Token telah dicabut.',
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var Pegawai $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nama_pegawai'   => ['required', 'string', 'max:255'],
            'email'          => ['required', 'string', 'email', 'max:255', 'unique:pegawai,email,' . $user->id],
            'nomor_hp'       => ['nullable', 'string', 'max:30'],
            'alamat_pegawai' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data profil tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'nama_pegawai'   => $request->input('nama_pegawai'),
            'email'          => $request->input('email'),
            'nomor_hp'       => $request->input('nomor_hp'),
            'alamat_pegawai' => $request->input('alamat_pegawai'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'id'            => $user->id,
                'nama_pegawai'  => $user->nama_pegawai,
                'username'      => $user->username,
                'email'         => $user->email,
                'role'          => $user->role->value ?? (string) $user->role,
                'nomor_hp'      => $user->nomor_hp,
                'alamat_pegawai'=> $user->alamat_pegawai,
                'is_owner'      => $user->isOwner(),
            ],
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        /** @var Pegawai $user */
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data password tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Password saat ini salah.',
                'errors' => [
                    'current_password' => ['Password saat ini tidak cocok.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => (string) $request->input('password'),
        ]);

        // Ganti password HARUS mencabut sesi API lama. Tanpa ini, token yang
        // sudah terlanjur bocor tetap berlaku selamanya meski passwordnya sudah
        // diganti — korban mengira dirinya sudah aman padahal tidak.
        $user->apiTokens()->delete();
        $tokenBaru = $user->createApiToken('Sesi setelah ganti password');

        return response()->json([
            'status' => 'success',
            'message' => 'Password berhasil diubah. Semua sesi API lain telah dikeluarkan.',
            'data' => [
                'token' => $tokenBaru,
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'service'   => 'Redline Backend API',
            'version'   => '1.1.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ─── Sesi aktif (token API milik sendiri) ──────────────────────

    /**
     * Daftar perangkat yang punya token aktif untuk akun ini.
     *
     * Untuk klien API, "sesi" adalah baris api_tokens — bukan tabel sessions
     * yang dipakai portal web Blade.
     */
    public function sesiIndex(Request $request): JsonResponse
    {
        /** @var Pegawai $user */
        $user = $request->user();
        $sekarang = $request->attributes->get('api_token_id');

        $sesi = $user->apiTokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApiToken $t) => [
                'id' => $t->id,
                'nama_perangkat' => $t->name,
                'terakhir_dipakai' => $t->last_used_at?->toIso8601String(),
                'dibuat' => $t->created_at?->toIso8601String(),
                'kedaluwarsa' => $t->expires_at?->toIso8601String(),
                'perangkat_ini' => $t->id === $sekarang,
            ]);

        return response()->json(['status' => 'success', 'data' => $sesi]);
    }

    /** Keluarkan satu perangkat. */
    public function sesiDestroy(Request $request, int $token): JsonResponse
    {
        /** @var Pegawai $user */
        $user = $request->user();

        // Dibatasi ke token milik pengguna ini: tanpa penyaringan pemilik,
        // id token milik pegawai lain bisa dihapus hanya dengan menebak angka.
        $baris = $user->apiTokens()->whereKey($token)->first();

        if (! $baris) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sesi tidak ditemukan.',
            ], 404);
        }

        if ($baris->id === $request->attributes->get('api_token_id')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gunakan Logout untuk mengakhiri sesi perangkat ini.',
            ], 422);
        }

        $baris->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Perangkat berhasil dikeluarkan.',
        ]);
    }

    /** Keluarkan semua perangkat lain, sisakan yang sedang dipakai. */
    public function sesiKeluarkanLain(Request $request): JsonResponse
    {
        /** @var Pegawai $user */
        $user = $request->user();
        $sekarang = $request->attributes->get('api_token_id');

        $jumlah = $user->apiTokens()
            ->when($sekarang !== null, fn ($q) => $q->whereKeyNot($sekarang))
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => $jumlah > 0
                ? "{$jumlah} perangkat lain dikeluarkan."
                : 'Tidak ada perangkat lain yang aktif.',
            'data' => ['dikeluarkan' => $jumlah],
        ]);
    }
}
