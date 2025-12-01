<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Disable all route loading for the worker.
     */
    public function boot(): void
    {
        // Worker does not load web or API routes
    }
}
