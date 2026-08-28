<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $header = $request->header('Authorization', '');
        $token = null;

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        } elseif ($request->filled('token')) {
            $token = (string) $request->input('token');
        }

        if (empty($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token autentikasi tidak ditemukan. Harap sertakan Bearer token pada header Authorization.',
            ], 401);
        }

        $hashed = hash('sha256', $token);
        $apiToken = ApiToken::with('pegawai')
            ->where('token', $hashed)
            ->first();

        // Also fallback to plain token check
        if (! $apiToken) {
            $apiToken = ApiToken::with('pegawai')
                ->where('token', $token)
                ->first();
        }

        if (! $apiToken || $apiToken->isExpired()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau sudah kedaluwarsa.',
            ], 401);
        }

        $pegawai = $apiToken->pegawai;
        if (! $pegawai || ! $pegawai->masih_bekerja) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun pegawai telah dinonaktifkan.',
            ], 403);
        }

        // Check roles if specified
        if (! empty($roles)) {
            $userRole = strtolower($pegawai->role->value ?? (string) $pegawai->role);
            $allowed = array_map('strtolower', $roles);
            if (! in_array($userRole, $allowed, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak: Anda tidak memiliki hak akses untuk tindakan ini.',
                ], 403);
            }
        }

        // Update token last used time
        $apiToken->updateQuietly(['last_used_at' => now()]);

        // Authenticate user for the request
        auth()->setUser($pegawai);
        $request->setUserResolver(fn () => $pegawai);

        return $next($request);
    }
}
