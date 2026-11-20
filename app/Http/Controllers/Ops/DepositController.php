<?php

namespace App\Http\Controllers\Ops;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Pricing\DepositService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepositController extends Controller
{
    public function settle(Request $request, Booking $booking, DepositService $deposits)
    {
        $this->authorize('manage-charges', $booking);

        $data = $request->validate([
            'action' => ['required', Rule::in(['release', 'apply'])],
            'notes' => 'nullable|string|max:255',
        ]);

        $deposit = $deposits->settle($booking, $data['action'], $request->user(), $data['notes'] ?? null);

        return back()->with('success', $deposit->applied_amount > 0
            ? 'RM '.number_format($deposit->applied_amount, 2).' of the deposit settled the extra charges; release RM '.number_format($deposit->releasedAmount(), 2).' to the customer.'
            : 'Deposit of RM '.number_format($deposit->amount, 2).' marked as released.');
    }
}
