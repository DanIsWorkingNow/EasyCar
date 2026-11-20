<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\ReturnOverdue;
use Illuminate\Console\Command;

/**
 * Hourly. Alerts the customer, the branch team and admins — once per
 * booking — when a picked-up car is past its due time plus grace.
 */
class FlagOverdueReturns extends Command
{
    protected $signature = 'rentals:flag-overdue';

    protected $description = 'Notify customers and staff about cars that are overdue for return';

    public function handle(): int
    {
        $overdue = Booking::approved()
            ->whereNotNull('picked_up_at')
            ->whereNull('completed_at')
            ->whereNull('overdue_notified_at')
            ->whereDate('end_date', '<=', today())
            ->with(['user', 'cars'])
            ->get()
            ->filter->isOverdue();

        foreach ($overdue as $booking) {
            $booking->user?->notify(new ReturnOverdue($booking));

            User::role('admin')
                ->get()
                ->merge(User::role('staff')->whereIn('branch_id', $booking->cars->pluck('branch_id'))->get())
                ->unique('id')
                ->each(fn (User $staff) => $staff->notify(new ReturnOverdue($booking, forStaff: true)));

            $booking->update(['overdue_notified_at' => now()]);
        }

        $this->info("Flagged {$overdue->count()} overdue return(s).");

        return self::SUCCESS;
    }
}
