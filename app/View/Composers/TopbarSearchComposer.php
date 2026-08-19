<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Enums\StatusService;
use App\Models\Service;
use Illuminate\View\View;

final class TopbarSearchComposer
{
    public function compose(View $view): void
    {
        $view->with('servisAktif', Service::query()
            ->with('perangkat')
            ->whereNotIn('status', [StatusService::Selesai, StatusService::SudahDiambil])
            ->latest('id')
            ->get(['id', 'nomor_resi', 'perangkat_id', 'status'])
            ->map(fn (Service $s): array => [
                'id' => $s->id,
                'resi' => $s->nomor_resi,
                'barang' => $s->perangkat->merk_model ?? '-',
                'status' => $s->status->value,
            ])
            ->values());
    }
}
