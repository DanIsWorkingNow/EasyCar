<?php

namespace App\Services;

use App\Enums\BookingPaymentStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Notifications\PaymentRefunded;
use App\Payments\Data\GatewayResult;
use App\Payments\Exceptions\InvalidSignatureException;
use App\Payments\Exceptions\PaymentException;
use App\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates payments independently of any one gateway:
 *
 *   startCheckout()  -> Payment row + hosted checkout URL
 *   handleWebhook()  -> verify signature -> log event idempotently ->
 *                       apply the state transition under a row lock
 *   handleReturn()   -> same pipeline for the browser redirect, when the
 *                       gateway can authenticate it
 *   refund()         -> gateway refund, or flag for manual processing
 *
 * State rules: a settled payment (paid / refunded) can never be moved
 * back to failed or cancelled by a late or replayed notification, and a
 * "paid" event is applied exactly once.
 */
class PaymentService
{
    public function __construct(private PaymentManager $gateways) {}

    /**
     * @throws PaymentException
     */
    public function startCheckout(Booking $booking, string $gatewayName): Payment
    {
        if (! $booking->canBePaid()) {
            throw new PaymentException('This booking can no longer be paid online.');
        }

        return $this->openCheckout($booking, $gatewayName, Payment::PURPOSE_BOOKING, (float) $booking->total_price);
    }

    /**
     * Hosted checkout for the booking's outstanding extra charges (late
     * return, fuel, damage…). The charge ids are snapshotted so the payment
     * settles exactly what the customer was shown.
     *
     * @throws PaymentException
     */
    public function startChargesCheckout(Booking $booking, string $gatewayName): Payment
    {
        $booking->loadMissing('charges');

        if (! $booking->canPayCharges()) {
            throw new PaymentException('There are no outstanding charges to pay.');
        }

        $chargeIds = $booking->charges->filter(fn ($c) => ! $c->isPaid() && ! $c->isVoided())->pluck('id')->values()->all();

        return $this->openCheckout($booking, $gatewayName, Payment::PURPOSE_CHARGES, $booking->outstandingCharges(), ['charge_ids' => $chargeIds]);
    }

    /**
     * Record money taken in person at a branch (cash, card terminal, bank
     * transfer) — for the rental itself at pick-up, or for extras at return.
     *
     * @throws PaymentException
     */
    public function recordCounterPayment(Booking $booking, string $purpose, string $method, ?string $reference, User $staff): Payment
    {
        return DB::transaction(function () use ($booking, $purpose, $method, $reference, $staff) {
            $booking = Booking::withTrashed()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $booking->load('charges');

            $outstanding = $purpose === Payment::PURPOSE_CHARGES
                ? $booking->charges->filter(fn ($c) => ! $c->isPaid() && ! $c->isVoided())
                : collect();

            $amount = match ($purpose) {
                Payment::PURPOSE_BOOKING => $booking->isPaid() ? 0.0 : (float) $booking->total_price,
                Payment::PURPOSE_CHARGES => round((float) $outstanding->sum('amount'), 2),
                default => throw new PaymentException('Unknown payment purpose.'),
            };

            if ($amount <= 0) {
                throw new PaymentException('Nothing is outstanding.');
            }

            // Any checkout still open online for the same thing is now moot.
            $booking->payments()->where('purpose', $purpose)->where('status', PaymentStatus::Pending->value)->update([
                'status' => PaymentStatus::Cancelled->value,
                'failure_reason' => 'Paid at the branch counter instead.',
            ]);

            $payment = $booking->payments()->create([
                'user_id' => $booking->user_id,
                'purpose' => $purpose,
                'gateway' => 'counter',
                'gateway_reference' => $reference ?: 'CTR-'.Str::upper(Str::random(10)),
                'amount' => $amount,
                'currency' => config('payments.currency', 'MYR'),
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                'meta' => ['method' => $method, 'recorded_by' => $staff->id, 'recorded_by_name' => $staff->name],
            ]);

            if ($purpose === Payment::PURPOSE_CHARGES) {
                BookingCharge::whereKey($outstanding->pluck('id'))->update(['paid_at' => now(), 'payment_id' => $payment->id]);
            } else {
                $booking->update(['payment_status' => BookingPaymentStatus::Paid]);
            }

            DB::afterCommit(fn () => $booking->user?->notify(new PaymentReceived($payment->fresh('booking'))));

            return $payment;
        });
    }

    /**
     * @throws PaymentException
     */
    private function openCheckout(Booking $booking, string $gatewayName, string $purpose, float $amount, array $meta = []): Payment
    {
        if (! $this->gateways->isAvailable($gatewayName)) {
            throw new PaymentException('That payment method is not available right now.');
        }

        $gateway = $this->gateways->driver($gatewayName);

        // Only one live checkout per booking and purpose: abandon earlier attempts.
        $booking->payments()->where('purpose', $purpose)->where('status', PaymentStatus::Pending->value)->update([
            'status' => PaymentStatus::Cancelled->value,
            'failure_reason' => 'Superseded by a new checkout.',
        ]);

        $payment = $booking->payments()->create([
            'user_id' => $booking->user_id,
            'purpose' => $purpose,
            'gateway' => $gatewayName,
            'amount' => round($amount, 2),
            'currency' => config('payments.currency', 'MYR'),
            'status' => PaymentStatus::Pending,
            'meta' => $meta ?: null,
        ]);

        try {
            $session = $gateway->createCheckout($payment, [
                'return' => route('payments.return', $payment),
                'cancel' => route('payments.return', ['payment' => $payment, 'cancelled' => 1]),
                'callback' => route('payments.webhook', $gatewayName),
            ]);
        } catch (\Throwable $e) {
            $message = $e instanceof PaymentException
                ? $e->getMessage()
                : 'The payment provider is unavailable. Please try again in a moment.';

            $payment->update(['status' => PaymentStatus::Failed, 'failure_reason' => $message]);

            Log::warning('Payment checkout could not be created', [
                'payment' => $payment->uuid,
                'gateway' => $gatewayName,
                'error' => $e->getMessage(),
                'context' => $e instanceof PaymentException ? $e->context : [],
            ]);

            throw $e instanceof PaymentException ? $e : new PaymentException($message, [], $e);
        }

        $payment->forceFill([
            'gateway_reference' => $session->reference,
            'checkout_url' => $session->redirectUrl,
        ]);
        $payment->mergeMeta($session->meta);
        $payment->save();

        return $payment;
    }

    /**
     * @throws InvalidSignatureException
     */
    public function handleWebhook(string $gatewayName, Request $request): ?Payment
    {
        $result = $this->gateways->resolve($gatewayName)->parseWebhook($request);

        return $this->record($gatewayName, $result);
    }

    public function handleReturn(Payment $payment, Request $request): Payment
    {
        if ($payment->isPending() && $request->boolean('cancelled')) {
            $payment->update(['status' => PaymentStatus::Cancelled, 'failure_reason' => 'You cancelled the checkout.']);

            return $payment;
        }

        if ($payment->isPending()) {
            try {
                $result = $this->gateways->driver($payment->gateway)->verifyReturn($request, $payment);

                if ($result) {
                    $this->record($payment->gateway, $result, $payment);
                }
            } catch (\Throwable $e) {
                // The webhook is authoritative; the return page simply
                // shows "processing" and polls until it lands.
                Log::info('Payment return could not be verified', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);
            }
        }

        return $payment->fresh();
    }

    /**
     * Idempotently apply one verified gateway statement.
     */
    public function record(string $gatewayName, GatewayResult $result, ?Payment $known = null): ?Payment
    {
        return DB::transaction(function () use ($gatewayName, $result, $known) {
            $event = PaymentEvent::createOrFirst(
                ['gateway' => $gatewayName, 'event_id' => $result->eventId],
                ['type' => $result->type, 'payload' => $result->payload],
            );

            if ($event->processed_at !== null) {
                return $event->payment; // replayed delivery — already applied
            }

            $payment = $known ?? $this->locate($gatewayName, $result);

            if ($payment) {
                $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                $this->assertBelongsTo($payment, $gatewayName, $result);

                if (! $result->isInformational()) {
                    $this->transition($payment, $result);
                }
            } else {
                Log::notice('Payment event for an unknown payment', ['gateway' => $gatewayName, 'event' => $result->eventId]);
            }

            $event->forceFill(['payment_id' => $payment?->id, 'processed_at' => now()])->save();

            return $payment;
        });
    }

    /**
     * Refund the booking's paid payment (used on rejection/cancellation).
     */
    public function refundBooking(Booking $booking, string $reason): ?Payment
    {
        $payment = $booking->payments()
            ->where('purpose', Payment::PURPOSE_BOOKING)
            ->where('status', PaymentStatus::Paid->value)
            ->latest('id')
            ->first();

        return $payment ? $this->refund($payment, $reason) : null;
    }

    /**
     * Full refund through the gateway when it has a refund API; otherwise
     * the payment is flagged refund_pending for finance to action and an
     * admin marks it complete from the console.
     *
     * @throws PaymentException when the gateway rejects the refund (the
     *                          payment is left flagged refund_pending)
     */
    public function refund(Payment $payment, string $reason): Payment
    {
        if (! $payment->isPaid()) {
            throw new PaymentException('Only paid payments can be refunded.');
        }

        $gateway = $this->gateways->driver($payment->gateway);
        $amount = round($payment->amount - $payment->refunded_amount, 2);
        $payment->mergeMeta(['refund_reason' => $reason]);

        if (! $gateway->supportsRefunds()) {
            $this->flagRefundPending($payment, 'Manual refund required ('.$payment->gatewayLabel().').');

            return $payment;
        }

        try {
            $refund = $gateway->refund($payment, $amount, $reason);
        } catch (PaymentException $e) {
            $this->flagRefundPending($payment, $e->getMessage());
            throw $e;
        }

        $this->completeRefund($payment, $amount, $refund->reference);

        return $payment;
    }

    /**
     * Admin confirms a manual (out-of-band) refund has been sent.
     */
    public function markRefundCompleted(Payment $payment, ?string $reference = null): Payment
    {
        if ($payment->status !== PaymentStatus::RefundPending) {
            throw new PaymentException('This payment is not awaiting a refund.');
        }

        $this->completeRefund($payment, round($payment->amount - $payment->refunded_amount, 2), $reference);

        return $payment;
    }

    private function transition(Payment $payment, GatewayResult $result): void
    {
        $payment->mergeMeta($result->meta);

        if ($result->gatewayReference && ! $payment->gateway_reference) {
            $payment->gateway_reference = $result->gatewayReference;
        }

        if ($result->status === PaymentStatus::Paid) {
            $this->markPaid($payment);

            return;
        }

        // Failed / cancelled only ever apply to a payment still in flight.
        if ($payment->isPending()) {
            $payment->status = $result->status;
            $payment->failure_reason = $result->failureReason;
        }

        $payment->save();
    }

    private function markPaid(Payment $payment): void
    {
        if ($payment->status->isSettled()) {
            $payment->save();

            return;
        }

        // Money moved, so accept it even if we'd already given up on this
        // attempt (e.g. a slow FPX confirmation after the user cancelled).
        $payment->forceFill(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'failure_reason' => null])->save();

        if ($payment->isForCharges()) {
            $this->settleCharges($payment);

            return;
        }

        $booking = Booking::withTrashed()->whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

        $alreadyPaidElsewhere = $booking->payments()
            ->whereKeyNot($payment->id)
            ->where('purpose', Payment::PURPOSE_BOOKING)
            ->where('status', PaymentStatus::Paid->value)
            ->exists();

        $bookingInactive = $booking->trashed() || in_array($booking->status, Booking::RELEASED_STATUSES, true);

        if ($alreadyPaidElsewhere || $bookingInactive) {
            // Duplicate payment, or paid after the booking was cancelled:
            // hand the money straight back.
            DB::afterCommit(function () use ($payment, $alreadyPaidElsewhere) {
                try {
                    $this->refund($payment->fresh(), $alreadyPaidElsewhere ? 'Duplicate payment' : 'Booking no longer active');
                } catch (\Throwable $e) {
                    Log::error('Automatic refund of orphaned payment failed', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);
                }
            });

            if (! $alreadyPaidElsewhere) {
                $booking->update(['payment_status' => BookingPaymentStatus::Paid]);
            }

            return;
        }

        $booking->update(['payment_status' => BookingPaymentStatus::Paid]);

        DB::afterCommit(fn () => $booking->user?->notify(new PaymentReceived($payment->fresh('booking'))));
    }

    /**
     * Mark the charges this payment was opened for as paid. If they were
     * meanwhile settled at the counter (or waived), the money is returned.
     */
    private function settleCharges(Payment $payment): void
    {
        $charges = BookingCharge::whereKey($payment->meta['charge_ids'] ?? [])
            ->where('booking_id', $payment->booking_id)
            ->outstanding()
            ->lockForUpdate()
            ->get();

        if ($charges->isEmpty()) {
            DB::afterCommit(function () use ($payment) {
                try {
                    $this->refund($payment->fresh(), 'Charges already settled');
                } catch (\Throwable $e) {
                    Log::error('Automatic refund of duplicate charges payment failed', ['payment' => $payment->uuid, 'error' => $e->getMessage()]);
                }
            });

            return;
        }

        BookingCharge::whereKey($charges->pluck('id'))->update(['paid_at' => now(), 'payment_id' => $payment->id]);

        DB::afterCommit(fn () => $payment->user?->notify(new PaymentReceived($payment->fresh('booking'))));
    }

    private function flagRefundPending(Payment $payment, string $note): void
    {
        $payment->mergeMeta(['refund_note' => $note]);
        $payment->status = PaymentStatus::RefundPending;
        $payment->save();

        if (! $payment->isForCharges()) {
            $payment->booking?->update(['payment_status' => BookingPaymentStatus::RefundPending]);
        }

        Log::warning('Refund requires manual action', ['payment' => $payment->uuid, 'note' => $note]);
    }

    private function completeRefund(Payment $payment, float $amount, ?string $reference): void
    {
        $payment->mergeMeta(array_filter(['refund_reference' => $reference]));
        $payment->forceFill([
            'status' => PaymentStatus::Refunded,
            'refunded_amount' => round($payment->refunded_amount + $amount, 2),
            'refunded_at' => now(),
        ])->save();

        if ($payment->isForCharges()) {
            $payment->charges()->whereNull('voided_at')->update([
                'voided_at' => now(),
                'void_reason' => Str::limit('Refunded: '.($payment->meta['refund_reason'] ?? 'refund issued'), 300, ''),
            ]);
        } else {
            $payment->booking?->update(['payment_status' => BookingPaymentStatus::Refunded]);
        }
        $payment->user?->notify(new PaymentRefunded($payment));
    }

    private function locate(string $gatewayName, GatewayResult $result): ?Payment
    {
        if ($result->paymentUuid) {
            $payment = Payment::where('uuid', $result->paymentUuid)->where('gateway', $gatewayName)->first();

            if ($payment) {
                return $payment;
            }
        }

        if ($result->gatewayReference) {
            return Payment::where('gateway', $gatewayName)->where('gateway_reference', $result->gatewayReference)->first();
        }

        return null;
    }

    /**
     * A notification may only touch a payment made through the same
     * gateway with the same provider reference — so a validly signed event
     * for one bill can't be replayed against another payment.
     */
    private function assertBelongsTo(Payment $payment, string $gatewayName, GatewayResult $result): void
    {
        if ($payment->gateway !== $gatewayName) {
            throw new InvalidSignatureException('Gateway mismatch for payment.');
        }

        if ($result->gatewayReference && $payment->gateway_reference
            && ! hash_equals((string) $payment->gateway_reference, (string) $result->gatewayReference)) {
            throw new InvalidSignatureException('Gateway reference mismatch for payment.');
        }
    }
}
