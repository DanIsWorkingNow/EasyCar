<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
    @php
        $utilPct = $kpis['fleet_utilization'] * 100;
        $utilTone = $utilPct >= 70 ? 'text-match' : ($utilPct >= 50 ? 'text-amber-300' : 'text-red-400');
    @endphp
    <x-stat label="Bookings" :value="number_format($kpis['total_bookings'])" icon="calendar" hint="in period" />
    <x-stat label="Pending" :value="$kpis['pending_approvals']" icon="clock" :tone="$kpis['pending_approvals'] ? 'text-amber-300' : 'text-white'" hint="awaiting review" />
    <x-stat label="Approved today" :value="$kpis['approved_today']" icon="check-circle" tone="text-match" />
    <x-stat label="Rejected" :value="$kpis['rejected_period']" icon="x-circle" tone="text-red-400" hint="in period" />
    <x-stat label="Revenue" :value="'RM '.number_format($kpis['revenue_period'], 0)" icon="cash" hint="approved + completed" />
    <div class="card card-pad">
        <div class="flex items-start justify-between"><p class="text-[11px] font-semibold tracking-wider text-subtle uppercase">Utilisation</p><x-icon name="chart" class="size-5 text-subtle" /></div>
        <p class="stat-value mt-3 {{ $utilTone }}">{{ number_format($utilPct, 1) }}%</p>
        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/10" role="meter" aria-valuenow="{{ round($utilPct) }}" aria-valuemin="0" aria-valuemax="100" aria-label="Fleet utilisation">
            <div class="h-full rounded-full {{ $utilPct >= 70 ? 'bg-match' : ($utilPct >= 50 ? 'bg-amber-400' : 'bg-red-500') }}" style="width: {{ min(100, $utilPct) }}%"></div>
        </div>
        <p class="mt-1.5 text-xs text-subtle">target 70%+</p>
    </div>
</div>
