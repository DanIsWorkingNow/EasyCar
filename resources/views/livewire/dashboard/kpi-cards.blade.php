<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs font-medium text-gray-500 uppercase">Total Bookings</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $kpis['total_bookings'] }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs font-medium text-gray-500 uppercase">Pending Approvals</div>
        <div class="text-2xl font-bold {{ $kpis['pending_approvals'] > 0 ? 'text-amber-600' : 'text-gray-900' }} mt-1">
            {{ $kpis['pending_approvals'] }}
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs font-medium text-gray-500 uppercase">Approved Today</div>
        <div class="text-2xl font-bold text-green-600 mt-1">{{ $kpis['approved_today'] }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs font-medium text-gray-500 uppercase">Rejected (period)</div>
        <div class="text-2xl font-bold text-red-500 mt-1">{{ $kpis['rejected_period'] }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs font-medium text-gray-500 uppercase">Revenue (period)</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">RM {{ number_format($kpis['revenue_period'], 2) }}</div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-xs font-medium text-gray-500 uppercase">Fleet Utilization</div>
        @php
            $utilPct = $kpis['fleet_utilization'] * 100;
            $utilColor = $utilPct >= 70 ? 'text-green-600' : ($utilPct >= 50 ? 'text-amber-600' : 'text-red-500');
        @endphp
        <div class="text-2xl font-bold {{ $utilColor }} mt-1">{{ number_format($utilPct, 1) }}%</div>
        <div class="text-xs text-gray-400">target 70%+</div>
    </div>
</div>
