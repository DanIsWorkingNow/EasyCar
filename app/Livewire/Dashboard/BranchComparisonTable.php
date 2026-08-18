<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * FR-DSH-04. Admin-only — DashboardIndex's view only ever mounts this
 * component when $isAdmin is true, but the check is repeated here too
 * since a Livewire component can in principle be reached directly.
 */
class BranchComparisonTable extends Component
{
    public int $period = 30;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);
    }

    public function render()
    {
        $from = now()->subDays($this->period - 1)->startOfDay();
        $to = now()->endOfDay();

        $rows = app(DashboardService::class)->getBranchComparison($from, $to);

        return view('livewire.dashboard.branch-comparison-table', ['rows' => $rows]);
    }
}
