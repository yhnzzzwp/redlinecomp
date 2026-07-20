<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromoRequest;
use App\Http\Requests\UpdatePromoRequest;
use App\Models\Promo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class PromoController extends Controller
{
    public function index(): View
    {
        return view('internal.promo.index', [
            'promo' => Promo::query()->latest()->paginate(12),
            'aktif' => Promo::query()->where('aktif', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('internal.promo.form', ['promo' => new Promo()]);
    }

    public function store(StorePromoRequest $request): RedirectResponse
    {
        Promo::query()->create($request->validated());

        return redirect()->route('promo.index')->with('success', 'Promo berhasil ditambahkan.');
    }

    public function edit(Promo $promo): View
    {
        return view('internal.promo.form', ['promo' => $promo]);
    }

    public function update(UpdatePromoRequest $request, Promo $promo): RedirectResponse
    {
        $promo->update($request->validated());

        return redirect()->route('promo.index')->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promo $promo): RedirectResponse
    {
        $promo->delete();

        return redirect()->route('promo.index')->with('success', 'Promo berhasil dihapus.');
    }
}
