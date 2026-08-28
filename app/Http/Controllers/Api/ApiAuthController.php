<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'portal'   => ['nullable', 'string', 'in:admin,karyawan'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Username dan password wajib diisi.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $login = trim($request->input('username'));
        $password = $request->input('password');
        $portal = $request->input('portal', 'admin');

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

        // Validate portal role access
        if ($portal === 'admin' && ! $pegawai->isOwner()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak: Akun Anda tidak memiliki hak akses Owner/Admin Console.',
            ], 403);
        }

        // Generate session bearer token
        $token = 'rl_tok_' . Str::random(40) . '_' . dechex((int) microtime(true));

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
                    'role'          => $pegawai->role->value ?? $pegawai->role,
                    'is_owner'      => $pegawai->isOwner(),
                ],
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status'    => 'ok',
            'service'   => 'Redline Backend API',
            'version'   => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
