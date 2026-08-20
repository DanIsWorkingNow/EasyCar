<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\RentalReminder;
use Illuminate\Console\Command;

/**
 * FR-NOT-03. Registered to run daily via routes/console.php (this app
 * schedules through routes/console.php, not app/Console/Kernel.php, since
 * bootstrap/app.php routes commands there).
 *
 * Finds bookings starting tomorrow (pick-up reminder) or ending tomorrow
 * (return reminder) and queues one RentalReminder each. Only approved
 * bookings are reminded — a still-pending booking has nothing to remind
 * the customer about yet.
 */
class SendRentalReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send pick-up and return reminders for bookings starting or ending tomorrow';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $pickups = Booking::approved()->whereDate('start_date', $tomorrow)->with('user')->get();
        foreach ($pickups as $booking) {
            $booking->user->notify(new RentalReminder($booking, 'pickup'));
        }

        $returns = Booking::approved()->whereDate('end_date', $tomorrow)->with('user')->get();
        foreach ($returns as $booking) {
            $booking->user->notify(new RentalReminder($booking, 'return'));
        }

        $this->info("Queued {$pickups->count()} pick-up and {$returns->count()} return reminder(s) for {$tomorrow}.");

        return self::SUCCESS;
    }
}
