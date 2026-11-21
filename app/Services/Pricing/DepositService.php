<?php

namespace App\Services\Pricing;

use App\Models\Booking;
use App\Models\BookingDeposit;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The refundable security deposit: taken once per booking at the first
 * pick-up (snapshotted as bookings.deposit_amount when booked), then after
 * the return either released in full or used to settle the extra charges
 * with the remainder released.
 */
class DepositService
{
    public function __construct(private PaymentService $payments) {}

    /** A deposit is due and hasn't been taken yet. */
    public function isDue(Booking $booking): bool
    {
        return (bool) config('pricing.deposit.required')
            && (float) $booking->deposit_amount > 0
            && ! $booking->deposit()->exists();
    }

    /**
     * @param  array{method: string, reference: ?string}  $data
     *
     * @throws ValidationException
     */
    public function collect(Booking $booking, array $data, User $staff): BookingDeposit
    {
        if (! array_key_exists($data['method'], config('pricing.deposit.methods'))) {
            throw ValidationException::withMessages(['deposit' => 'Choose how the deposit was taken.']);
        }

        return $booking->deposit()->create([
            'amount' => $booking->deposit_amount,
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'status' => BookingDeposit::HELD,
            'collected_by' => $staff->id,
            'collected_at' => now(),
        ]);
    }

    /** Extra charges the deposit could settle right now. */
    public function applicableAmount(Booking $booking): float
    {
        $deposit = $booking->deposit;
        $outstanding = $booking->outstandingCharges();

        return $deposit?->isHeld() && $outstanding > 0 && $outstanding <= $deposit->amount ? $outstanding : 0.0;
    }

    /**
     * @param  'release'|'apply'  $action
     *
     * @throws ValidationException
     */
    public function settle(Booking $booking, string $action, User $staff, ?string $notes = null): BookingDeposit
    {
        return DB::transaction(function () use ($booking, $action, $staff, $notes) {
            $deposit = BookingDeposit::where('booking_id', $booking->id)->lockForUpdate()->first();

            if (! $deposit?->isHeld()) {
                throw ValidationException::withMessages(['deposit' => 'There is no deposit held on this booking.']);
            }
            if ($booking->isOnTrip()) {
                throw ValidationException::withMessages(['deposit' => 'Settle the deposit after the car is returned.']);
            }

            $applied = 0.0;
            if ($action === 'apply') {
                $booking->load('charges');
                $amount = $this->applicableAmount($booking->setRelation('deposit', $deposit));
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['deposit' => 'The deposit can only settle charges when it covers all of them. Collect the charges, then release the deposit.']);
                }
                $payment = $this->payments->recordCounterPayment($booking, Payment::PURPOSE_CHARGES, 'deposit', 'DEP-'.$deposit->id, $staff);
                $applied = (float) $payment->amount;
            }

            $deposit->update([
                'status' => $applied > 0 ? BookingDeposit::APPLIED : BookingDeposit::RELEASED,
                'applied_amount' => $applied,
                'settled_by' => $staff->id,
                'settled_at' => now(),
                'notes' => $notes,
            ]);

            return $deposit;
        });
    }
}
