<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VehicleInspection;
use Illuminate\Support\Facades\Gate;

class VehicleInspectionPolicy
{
    /**
     * The customer on the booking, staff at the car's branch, and admins
     * (via Gate::before) may see a handover report, its photos and its
     * signature.
     */
    public function view(User $user, VehicleInspection $inspection): bool
    {
        $inspection->loadMissing(['booking', 'car']);

        return $inspection->booking?->user_id === $user->id
            || Gate::forUser($user)->allows('handover', $inspection->car);
    }
}
