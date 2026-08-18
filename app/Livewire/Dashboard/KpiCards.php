<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

/**
 * FR-DSH-01 (stat cards) + FR-DSH-02 (fleet utilization). Receives its scope
 * as props from DashboardIndex — refresh happens via the parent's
 * wire:poll, not an independent poll here, to avoid every widget polling
 * the server on its own schedule.
 */
class KpiCards extends Component
{
    public ?int $branchId = null;
    public int $period = 30;

    public function render()
    {
        $service = app(DashboardService::class);
        $from = now()->subDays($this->period - 1)->startOfDay();
        $to = now()->endOfDay();

        $kpis = $service->getKpis($this->branchId, $from, $to);

        return view('livewire.dashboard.kpi-cards', ['kpis' => $kpis]);
    }
}
