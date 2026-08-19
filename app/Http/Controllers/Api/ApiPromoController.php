<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\JsonResponse;

class ApiPromoController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();
        $promos = Promo::query()
            ->where('aktif', true)
            ->where(fn ($q) => $q->whereNull('waktu_mulai')->orWhere('waktu_mulai', '<=', $now))
            ->where(fn ($q) => $q->whereNull('waktu_berakhir')->orWhere('waktu_berakhir', '>=', $now))
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $promos,
        ]);
    }
}
