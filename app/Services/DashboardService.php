<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Level 2 (FSD Section 5.7 / 10, TSD Section 5.2). Every method is scoped by
 * an optional $branchId: null means "all branches" (Admin's default view),
 * an integer means "this branch only" (what Staff are always locked to).
 *
 * Kept as a plain service class, not a Livewire component, so it's equally
 * usable from the Livewire widgets below and — later — from the REST API
 * controllers without duplicating a single query.
 */
class DashboardService
{
    private const CACHE_TTL_SECONDS = 30;

    public function getKpis(?int $branchId, Carbon $from, Carbon $to): array
    {
        return Cache::remember($this->cacheKey('kpis', $branchId, $from, $to), self::CACHE_TTL_SECONDS, function () use ($branchId, $from, $to) {
            $bookingQuery = $this->bookingsInScope($branchId);

            return [
                'total_bookings' => (clone $bookingQuery)->whereBetween('bookings.created_at', [$from, $to])->count(),
                'pending_approvals' => (clone $bookingQuery)->where('status', 'pending')->count(),
                'approved_today' => (clone $bookingQuery)->where('status', 'approved')->whereDate('approved_at', now())->count(),
                'rejected_period' => (clone $bookingQuery)->where('status', 'rejected')->whereBetween('bookings.created_at', [$from, $to])->count(),
                'revenue_period' => (float) (clone $bookingQuery)
                    ->whereIn('status', ['approved', 'completed'])
                    ->whereBetween('bookings.created_at', [$from, $to])
                    ->sum('total_price'),
                'fleet_utilization' => $this->getUtilization($branchId, $from, $to),
            ];
        });
    }

    /**
     * Booked car-days ÷ available car-days over [$from, $to] (TSD Section 9.2).
     * Only approved/completed bookings count as "booked" — pending and
     * rejected bookings never occupied a car.
     *
     * FIX: the original expression used MySQL's DATEDIFF(), which does not
     * exist on SQLite (local dev's default connection per .env — see the
     * comment in the total_price migration re: the same MySQL/SQLite split).
     * This branches on the active driver so it works against both.
     */
    public function getUtilization(?int $branchId, Carbon $from, Carbon $to): float
    {
        $periodDays = (int) $from->diffInDays($to) + 1;

        $carCount = Car::when($branchId, function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->count();

        $availableCarDays = $carCount * $periodDays;

        // FIX: diffInDays() (Carbon 3) can return a float with float precision
        // noise (e.g. 30.999999999988425 instead of clean 31), which makes
        // $carCount * $periodDays a float 0.0 when there are no cars — and
        // `=== 0` (strict int comparison) never matches a float zero, so this
        // guard silently fell through to a real DivisionByZeroError below.
        if ($availableCarDays <= 0) {
            return 0.0;
        }

        $dayCountExpr = DB::connection()->getDriverName() === 'sqlite'
            ? 'julianday(car_booking.rental_end) - julianday(car_booking.rental_start) + 1'
            : 'DATEDIFF(car_booking.rental_end, car_booking.rental_start) + 1';

        $bookedCarDays = DB::table('car_booking')
            ->join('bookings', 'bookings.id', '=', 'car_booking.booking_id')
            ->join('cars', 'cars.id', '=', 'car_booking.car_id')
            ->whereNull('bookings.deleted_at')
            ->whereIn('bookings.status', ['approved', 'completed'])
            ->when($branchId, function ($q) use ($branchId) {
                $q->where('cars.branch_id', $branchId);
            })
            ->whereBetween('car_booking.rental_start', [$from->toDateString(), $to->toDateString()])
            ->sum(DB::raw($dayCountExpr));

        return round($bookedCarDays / $availableCarDays, 4);
    }

    /**
     * One {date, count} pair per day for the last $days days — feeds the
     * ApexCharts trend widget directly.
     */
    public function getBookingTrend(?int $branchId, int $days = 30): array
    {
        return Cache::remember("dashboard:trend:{$branchId}:{$days}", self::CACHE_TTL_SECONDS, function () use ($branchId, $days) {
            $from = now()->subDays($days - 1)->startOfDay();

            $rows = $this->bookingsInScope($branchId)
                ->where('bookings.created_at', '>=', $from)
                ->selectRaw('DATE(bookings.created_at) as day, COUNT(*) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $series = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $from->copy()->addDays($i)->toDateString();
                $series[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
            }

            return $series;
        });
    }

    /**
     * One row per branch — Admin-only (FR-DSH-04).
     */
    public function getBranchComparison(Carbon $from, Carbon $to): array
    {
        return Cache::remember($this->cacheKey('branch-comparison', null, $from, $to), self::CACHE_TTL_SECONDS, function () use ($from, $to) {
            return Branch::all()->map(function ($branch) use ($from, $to) {
                $kpis = $this->getKpis($branch->id, $from, $to);

                return [
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'total_bookings' => $kpis['total_bookings'],
                    'revenue_period' => $kpis['revenue_period'],
                    'fleet_utilization' => $kpis['fleet_utilization'],
                ];
            })->values()->all();
        });
    }

    /**
     * Not cached — this backs the live polling widget (FR-DSH-05, FR-DSH-11)
     * and must always reflect the latest approve/reject actions.
     */
    public function getPendingQueue(?int $branchId, int $limit = 25)
    {
        return $this->bookingsInScope($branchId)
            ->where('status', 'pending')
            ->with(['user', 'cars'])
            ->orderBy('bookings.start_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Base query for "bookings visible in this scope" — branch-filtered via
     * the cars attached to each booking, matching how BookingAvailabilityService
     * and Staff\BookingController already scope by branch.
     */
    private function bookingsInScope(?int $branchId)
    {
        $query = Booking::query()->select('bookings.*');

        if ($branchId) {
            $query->whereHas('cars', fn ($q) => $q->where('branch_id', $branchId));
        }

        return $query;
    }

    private function cacheKey(string $prefix, ?int $branchId, Carbon $from, Carbon $to): string
    {
        return sprintf('dashboard:%s:%s:%s:%s', $prefix, $branchId ?? 'all', $from->toDateString(), $to->toDateString());
    }

    /**
     * Called from Booking::approve()/reject() so the 30-second cache never
     * shows stale pending-approval counts for longer than a single request
     * after an action — the dashboard's own 10-15s poll (FR-DSH-11) picks up
     * the rest.
     */
    public static function forgetCacheFor(?int $branchId): void
    {
        $today = now()->toDateString();
        Cache::forget('dashboard:kpis:'.($branchId ?? 'all').":{$today}:{$today}");
        // Trend/branch-comparison caches expire naturally within 30s; not
        // worth enumerating every possible date-range key on every action.
    }
}
