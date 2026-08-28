<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\PromoTidakValidException;
use App\Http\Controllers\Controller;
use App\Models\Promo;
use App\Services\PromoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiPromoController extends Controller
{
    public function __construct(
        private readonly PromoService $promoService
    ) {}

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

    public function cek(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'kode_promo' => ['required', 'string'],
            'subtotal'   => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter tidak valid.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $kode = trim((string) $request->input('kode_promo'));
        $subtotal = (int) $request->input('subtotal');

        try {
            $result = $this->promoService->hitung($kode, $subtotal);
            $promo = Promo::find($result->promoId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Kode promo valid.',
                'data'    => [
                    'promo_id'     => $result->promoId,
                    'kode_promo'   => $promo?->kode_promo,
                    'nama_promo'   => $promo?->nama_promo,
                    'diskon'       => $result->diskon,
                    'total_setelah_diskon' => max(0, $subtotal - $result->diskon),
                ],
            ]);
        } catch (PromoTidakValidException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
