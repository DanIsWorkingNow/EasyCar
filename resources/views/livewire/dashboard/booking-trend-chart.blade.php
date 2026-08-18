<div class="bg-white rounded-lg shadow p-4">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">Booking Volume Trend ({{ count($series) }}d)</h2>

    {{-- wire:ignore keeps Livewire's DOM diffing away from this element —
         ApexCharts owns everything inside it after the first render. The
         chart itself is updated by listening for the 'booking-trend-updated'
         event this component dispatches on every render (including after
         the parent's wire:poll), rather than by re-reading the DOM. --}}
    <div wire:ignore id="booking-trend-chart-{{ $this->getId() }}" style="min-height: 220px;"></div>
</div>

@script
<script>
    (function () {
        const el = document.getElementById('booking-trend-chart-{{ $this->getId() }}');
        const initialSeries = @js(collect($series)->pluck('count')->all());
        const categories = @js(collect($series)->pluck('date')->all());

        const chart = new ApexCharts(el, {
            chart: { type: 'area', height: 220, toolbar: { show: false } },
            series: [{ name: 'Bookings', data: initialSeries }],
            xaxis: { categories: categories, labels: { show: false } },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            colors: ['#4f46e5'],
        });
        chart.render();

        $wire.on('booking-trend-updated', (event) => {
            const payload = Array.isArray(event) ? event[0] : event;
            chart.updateOptions({
                series: [{ name: 'Bookings', data: payload.series.map(p => p.count) }],
                xaxis: { categories: payload.series.map(p => p.date) },
            });
        });
    })();
</script>
@endscript
