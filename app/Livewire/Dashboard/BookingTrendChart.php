<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Component;

/**
 * FR-DSH-03. Livewire computes the series server-side; the actual chart
 * rendering is ApexCharts, bridged via a browser event rather than a raw
 * DOM node lookup, since Livewire may re-render this component's wrapper
 * between polls.
 */
class BookingTrendChart extends Component
{
    public ?int $branchId = null;

    public int $period = 30;

    public function render()
    {
        $series = app(DashboardService::class)->getBookingTrend($this->branchId, $this->period);

        $this->dispatch('booking-trend-updated', series: $series);

        return view('livewire.dashboard.booking-trend-chart', ['series' => $series]);
    }
}
