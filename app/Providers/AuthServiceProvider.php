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
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates here
        Gate::define('admin-only', function (User $user) {
            return $user->userLevel === 5;
        });

        Gate::define('admin-staff', function (User $user) {
            return $user->userLevel >= 1;
        });
    }
}
