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

        // Hanya menerima token lewat header Authorization. Token pada query
        // string / body ikut tercatat di access log nginx, log Cloudflare,
        // header Referer, dan riwayat browser.
        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        }

        if (empty($token)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token autentikasi tidak ditemukan. Harap sertakan Bearer token pada header Authorization.',
            ], 401);
        }

        // Pegawai::createApiToken() HANYA menyimpan hash sha256, jadi
        // pencarian token mentah tidak pernah cocok untuk token yang sah —
        // fallback lama justru membuat isi tabel api_tokens bisa langsung
        // dipakai ulang sebagai kredensial bila database bocor.
        $hashed = hash('sha256', $token);
        $apiToken = ApiToken::with('pegawai')
            ->where('token', $hashed)
            ->first();

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

        // Id token yang sedang dipakai, supaya halaman "Sesi Aktif" bisa
        // menandai perangkat ini dan tidak mengeluarkan dirinya sendiri.
        $request->attributes->set('api_token_id', $apiToken->id);

        // Authenticate user for the request
        auth()->setUser($pegawai);
        $request->setUserResolver(fn () => $pegawai);

        return $next($request);
    }
}
