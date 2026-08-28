<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isOwner') || ! $user->isOwner()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak: Fitur ini hanya dapat diakses oleh Owner / Admin.',
            ], 403);
        }

        return $next($request);
    }
}
