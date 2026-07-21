<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('promo:deactivate', function () {
    $count = \App\Models\Promo::query()
        ->whereDate('waktu_berakhir', '<', today())
        ->where('aktif', true)
        ->update(['aktif' => false]);
        
    $this->info("Deactivated {$count} expired promos.");
})->purpose('Deactivate expired promos');

\Illuminate\Support\Facades\Schedule::command('promo:deactivate')->daily();
