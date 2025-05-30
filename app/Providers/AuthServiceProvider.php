<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Car;
use App\Policies\CarPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
       Car::class => CarPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
   

    public function boot()
{
    Gate::define('is-admin', fn ($user) => $user->userLevel === 5);
    Gate::define('is-staff', fn ($user) => $user->userLevel === 1);
}
}
