<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Demo data for the Level 2 dashboard (KPI cards, trend chart, branch
 * comparison, pending-approval queue). NOT part of the default `db:seed`
 * run — DatabaseSeeder doesn't call this, since a fresh/production install
 * shouldn't get fake customer bookings by default. Run explicitly:
 *
 *   php artisan db:seed --class=Database\Seeders\BookingDemoSeeder
 *
 * Generates ~55 bookings spread across the last 45 days of created_at (so
 * the trend chart has an actual curve instead of a flat line), with a mix
 * of statuses and rental windows chosen so every dashboard widget has
 * something to show:
 *
 *   - Fleet Utilization only counts approved/completed bookings whose
 *     rental_start falls inside the trailing period window. A real booking
 *     can never do this — the app requires 2+ days' advance notice, so a
 *     live booking's rental_start is always in the future. This seeder
 *     deliberately backdates most approved bookings (and all completed
 *     ones) to represent rental history that's already happened.
 *   - Pending Approval Queue needs pending bookings with near-future
 *     rental dates — the only status that stays future-dated here.
 *   - Branch Performance Comparison needs bookings spread across all three
 *     branches rather than concentrated in one.
 */
class BookingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('userLevel', 5)->first();
        $customers = User::where('userLevel', 0)->get();
        $cars = Car::all();

        if ($customers->isEmpty() || $cars->isEmpty() || ! $admin) {
            $this->command?->warn('BookingDemoSeeder needs BranchSeeder/CarSeeder/UserSeeder to have run first — skipping.');

            return;
        }

        $carsByBranch = $cars->groupBy('branch_id');
        $totalBookings = 55;
        $created = 0;

        for ($i = 0; $i < $totalBookings; $i++) {
            $branchId = $carsByBranch->keys()->random();
            $branchCars = $carsByBranch[$branchId];

            $pickCount = min(rand(1, 2), $branchCars->count());
            $pickedCars = $branchCars->random($pickCount);
            $pickedCars = $pickedCars instanceof Collection ? $pickedCars : collect([$pickedCars]);

            $status = $this->pickStatus();
            $createdAt = Carbon::now()->subDays(rand(0, 44))->subHours(rand(0, 23));
            [$startDate, $endDate, $days] = $this->pickRentalWindow($status);

            $totalPrice = $pickedCars->sum(fn ($car) => $car->price_per_day * $days);

            $attributes = [
                'user_id' => $customers->random()->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $days,
                'status' => $status,
                'total_price' => $totalPrice,
            ];

            if (in_array($status, ['approved', 'completed', 'rejected'], true)) {
                $attributes['approved_by'] = $admin->id;
                $attributes['approved_at'] = $createdAt->copy()->addHours(rand(1, 12));
                $attributes['approval_notes'] = $status !== 'rejected' ? 'Looks good, approved.' : null;
                $attributes['rejection_reason'] = $status === 'rejected' ? 'Car unavailable for the requested dates.' : null;
            }

            $booking = Booking::create($attributes);
            $booking->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            foreach ($pickedCars as $car) {
                $booking->cars()->attach($car->id, [
                    'rental_start' => $startDate,
                    'rental_end' => $endDate,
                    'quantity' => 1,
                    'price' => $car->price_per_day * $days,
                ]);
            }

            $created++;
        }

        $this->command?->info("BookingDemoSeeder: created {$created} demo bookings.");
    }

    private function pickStatus(): string
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 50 => 'approved',   // most bookings end up approved
            $roll <= 65 => 'completed',  // rental already happened
            $roll <= 85 => 'pending',    // still awaiting a decision
            default => 'rejected',
        };
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    private function pickRentalWindow(string $status): array
    {
        $length = rand(1, 5);

        $start = match ($status) {
            // Always future — matches the app's real "2+ days' notice" rule,
            // and is what the Pending Approval Queue widget wants to show.
            'pending' => Carbon::now()->addDays(rand(2, 20)),
            // Always past — the rental already happened.
            'completed' => Carbon::now()->subDays(rand($length + 1, 40)),
            // approved/rejected — mostly past (so Fleet Utilization has
            // history to compute against), some upcoming.
            default => rand(1, 100) <= 70
                ? Carbon::now()->subDays(rand($length + 1, 35))
                : Carbon::now()->addDays(rand(2, 15)),
        };

        $end = $start->copy()->addDays($length - 1);

        return [$start->toDateString(), $end->toDateString(), $length];
    }
}
