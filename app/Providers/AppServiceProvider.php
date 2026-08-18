<?php

namespace App\Providers;

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
    public function boot()
    {
        // The 'admin'/'staff' middleware aliases previously registered here
        // (TD-06) are gone — routes now use Spatie's 'role:admin'/'role:staff'
        // middleware, registered automatically by the package.
    }
}
