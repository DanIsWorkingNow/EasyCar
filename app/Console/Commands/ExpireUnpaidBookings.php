<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Console\Command;

/**
 * Releases cars held by pending bookings whose payment never arrived, so
 * an abandoned checkout can't block a car indefinitely. A booking with a
 * checkout opened in the last 30 minutes is left alone (the customer may
 * still be on the bank's page).
 */
class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid {--dry-run : List what would be cancelled}';

    protected $description = 'Cancel pending bookings that were not paid within the payment hold window';

    public function handle(): int
    {
        $hold = (int) config('payments.hold_minutes');

        if (! config('payments.enabled') || $hold <= 0) {
            $this->info('Payment holds are disabled — nothing to do.');

            return self::SUCCESS;
        }

        $expired = Booking::pending()
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<', now()->subMinutes($hold))
            ->whereDoesntHave('payments', fn ($q) => $q
                ->where('status', PaymentStatus::Pending->value)
                ->where('created_at', '>', now()->subMinutes(30)))
            ->get();

        foreach ($expired as $booking) {
            if ($this->option('dry-run')) {
                $this->line("Would cancel {$booking->reference}");

                continue;
            }

            $booking->cancel(null, "Payment was not received within {$hold} minutes.");
        }

        $this->info(($this->option('dry-run') ? 'Would cancel ' : 'Cancelled ').$expired->count().' unpaid booking(s).');

        return self::SUCCESS;
    }
}
