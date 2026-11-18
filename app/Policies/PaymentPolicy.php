<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->id === $payment->user_id;
    }

    public function refund(User $user, Payment $payment): bool
    {
        return false; // admin only, via Gate::before()
    }
}
