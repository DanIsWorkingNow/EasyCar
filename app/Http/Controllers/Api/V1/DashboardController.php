<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Services\DashboardService;
use Illuminate\Http\Request;

/**
 * TSD Section 8.6. Every method here just calls the exact same
 * DashboardService (Level 2) the Livewire dashboard calls directly — this
 * controller exists so a future mobile client or kiosk can get the same
 * KPIs without duplicating a single query.
 */
class DashboardController extends Controller
{
    public function kpis(Request $request, DashboardService $service)
    {
        $this->authorizeDashboard($request);

        $branchId = $this->resolveBranchId($request);
        $period = (int) $request->input('period', 30);
        $from = now()->subDays($period - 1)->startOfDay();
        $to = now()->endOfDay();

        return response()->json([
            'data' => $service->getKpis($branchId, $from, $to),
            'meta' => ['branch_id' => $branchId, 'period_days' => $period],
        ]);
    }

    public function trend(Request $request, DashboardService $service)
    {
        $this->authorizeDashboard($request);

        $branchId = $this->resolveBranchId($request);
        $days = (int) $request->input('days', 30);

        return response()->json(['data' => $service->getBookingTrend($branchId, $days)]);
    }

    public function branchComparison(Request $request, DashboardService $service)
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $period = (int) $request->input('period', 30);
        $from = now()->subDays($period - 1)->startOfDay();
        $to = now()->endOfDay();

        return response()->json(['data' => $service->getBranchComparison($from, $to)]);
    }

    public function pendingQueue(Request $request, DashboardService $service)
    {
        $this->authorizeDashboard($request);

        $branchId = $this->resolveBranchId($request);
        $limit = (int) $request->input('limit', 25);

        return BookingResource::collection($service->getPendingQueue($branchId, $limit));
    }

    private function authorizeDashboard(Request $request): void
    {
        abort_unless($request->user()->can('view-dashboard'), 403);
    }

    /**
     * FR-DSH-08/09, enforced the same way DashboardIndex's Livewire mount()
     * enforces it (Level 2): Staff never get a client-controllable branch_id,
     * Admin defaults to all-branch and may filter.
     */
    private function resolveBranchId(Request $request): ?int
    {
        $user = $request->user();

        if ($user->hasRole('staff')) {
            return $user->branch_id;
        }

        return $request->filled('branch_id') ? (int) $request->branch_id : null;
    }
}
