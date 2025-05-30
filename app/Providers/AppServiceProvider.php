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
       // Register middleware
    $router = $this->app['router'];
    $router->aliasMiddleware('admin', \App\Http\Middleware\AdminMiddleware::class);
    $router->aliasMiddleware('staff', \App\Http\Middleware\StaffMiddleware::class); //
    }
}
