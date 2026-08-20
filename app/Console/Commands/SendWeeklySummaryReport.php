<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\WeeklySummaryReport;
use App\Services\DashboardService;
use Illuminate\Console\Command;

/**
 * FR-RPT-03. Reuses DashboardService for the utilization figure per branch
 * (Level 2) rather than recomputing it — one formula, one place, same as
 * everywhere else in this project.
 */
class SendWeeklySummaryReport extends Command
{
    protected $signature = 'reports:weekly-summary';

    protected $description = 'Email a weekly operational summary to all admins';

    public function handle(DashboardService $dashboard): int
    {
        $from = now()->subDays(7)->startOfDay();
        $to = now()->endOfDay();

        $summary = [
            'period' => $from->format('M d').' – '.$to->format('M d, Y'),
            'total_bookings' => Booking::whereBetween('created_at', [$from, $to])->count(),
            'approved' => Booking::where('status', 'approved')->whereBetween('created_at', [$from, $to])->count(),
            'rejected' => Booking::where('status', 'rejected')->whereBetween('created_at', [$from, $to])->count(),
            'pending' => Booking::where('status', 'pending')->count(),
            'revenue' => (float) Booking::whereIn('status', ['approved', 'completed'])
                ->whereBetween('created_at', [$from, $to])
                ->sum('total_price'),
            'by_branch' => Branch::all()->map(function ($branch) use ($dashboard, $from, $to) {
                $kpis = $dashboard->getKpis($branch->id, $from, $to);

                return [
                    'name' => $branch->name,
                    'bookings' => $kpis['total_bookings'],
                    'utilization' => $kpis['fleet_utilization'],
                ];
            })->all(),
        ];

        $admins = User::role('admin')->get();

        foreach ($admins as $admin) {
            $admin->notify(new WeeklySummaryReport($summary));
        }

        $this->info("Weekly summary queued for {$admins->count()} admin(s).");

        return self::SUCCESS;
    }
}
