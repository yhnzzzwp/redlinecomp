<?php

namespace Tests;

use App\Support\Portal;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * Arahkan pembuatan URL (route()) ke host portal tertentu sehingga
     * request test mendarat di subdomain yang benar: 'public' | 'staff' | 'admin'.
     */
    protected function usePortal(string $portal): void
    {
        $root = 'http://'.Portal::from($portal)->host();

        config(['app.url' => $root]);
        URL::forceRootUrl($root);
    }
}
