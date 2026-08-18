<?php

namespace App\Policies;

use App\Models\Car;
use App\Models\User;

/**
 * FIXED (TD-16). The original CarPolicy was registered in
 * AuthServiceProvider but never actually invoked by any controller —
 * viewAny()/view() unconditionally returned false, and create()/update()
 * duplicated (inconsistently) the middleware's userLevel checks. This
 * version is backed by the Spatie permission model, and is safe to
 * actually wire up with $this->authorize() calls.
 */
class CarPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // browsing the fleet is fine for any authenticated user
    }

    public function view(User $user, Car $car): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') && $user->can('manage-fleet');
    }

    public function update(User $user, Car $car): bool
    {
        if (! $user->can('manage-fleet')) {
            return false;
        }

        return $user->hasRole('admin') || $car->branch_id === $user->branch_id;
    }

    public function delete(User $user, Car $car): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, Car $car): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Car $car): bool
    {
        return $user->hasRole('admin');
    }
}
