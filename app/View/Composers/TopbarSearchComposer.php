<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\StatusService;
use App\Models\Service;
use Illuminate\View\View;

/**
 * Menyediakan data servis aktif untuk dropdown pencarian topbar internal —
 * query keluar dari Blade (arsitektur: view bebas logika data).
 */
final class TopbarSearchComposer
{
    public function compose(View $view): void
    {
        $view->with('servisAktif', Service::query()
            ->whereNotIn('status', [StatusService::Selesai, StatusService::SudahDiambil])
            ->latest('id')
            ->get(['id', 'nomor_resi', 'nama_barang', 'status'])
            ->map(fn (Service $s): array => [
                'id' => $s->id,
                'resi' => $s->nomor_resi,
                'barang' => $s->nama_barang,
                'status' => $s->status->value,
            ])
            ->values());
    }
}
