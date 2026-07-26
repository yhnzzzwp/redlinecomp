<?php

declare(strict_types=1);

namespace App\Providers;

use App\View\Composers\TopbarSearchComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('components.layouts.app', TopbarSearchComposer::class);
    }
}
