<div class="card card-pad">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold">Booking volume</h2>
        <span class="text-xs text-subtle">last {{ count($series) }} days · {{ collect($series)->sum('count') }} total</span>
    </div>
    {{-- ApexCharts owns this node after first render; updates arrive via the
         'booking-trend-updated' event dispatched on every Livewire render. --}}
    <div wire:ignore id="booking-trend-chart-{{ $this->getId() }}" class="-mx-2 mt-2 min-h-[260px]"></div>
</div>

@script
<script>
    const el = document.getElementById('booking-trend-chart-{{ $this->getId() }}');
    const initial = @js($series);

    window.loadApexCharts().then((ApexCharts) => {
        const chart = new ApexCharts(el, {
            chart: { type: 'area', height: 260, toolbar: { show: false }, background: 'transparent', foreColor: '#8c8c8c', fontFamily: 'Inter, sans-serif', animations: { speed: 400 } },
            theme: { mode: 'dark' },
            series: [{ name: 'Bookings', data: initial.map(p => p.count) }],
            xaxis: { categories: initial.map(p => p.date), type: 'datetime', labels: { datetimeUTC: false, format: 'dd MMM' }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { min: 0, forceNiceScale: true, labels: { formatter: v => Math.round(v) } },
            grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 3 },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            colors: ['#e50914'],
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0, stops: [0, 95] } },
            tooltip: { theme: 'dark', x: { format: 'dd MMM yyyy' } },
        });
        chart.render();

        $wire.on('booking-trend-updated', (event) => {
            const payload = Array.isArray(event) ? event[0] : event;
            chart.updateOptions({
                series: [{ name: 'Bookings', data: payload.series.map(p => p.count) }],
                xaxis: { categories: payload.series.map(p => p.date) },
            });
        });
    });
</script>
@endscript
