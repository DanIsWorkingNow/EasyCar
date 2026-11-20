<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class InvoicePolicy
{
    /** The customer billed, branch staff for that booking, admins (Gate::before). */
    public function view(User $user, Invoice $invoice): bool
    {
        return $invoice->user_id === $user->id
            || ($invoice->booking && Gate::forUser($user)->allows('manage-charges', $invoice->booking));
    }
}
