<?php

namespace App\Providers;

use App\Models\Pemakaian;
use App\Observers\PemakaianObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pemakaian::observe(PemakaianObserver::class);
    }
}
