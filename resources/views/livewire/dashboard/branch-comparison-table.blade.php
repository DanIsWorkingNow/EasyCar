<div class="card">
    <div class="flex items-center justify-between px-5 pt-5">
        <h2 class="font-semibold">Branch performance</h2>
        <span class="text-xs text-subtle">last {{ $period }} days</span>
    </div>
    @php($maxRevenue = max(1, collect($rows)->max('revenue_period')))
    <div class="overflow-x-auto">
        <table class="table-ec mt-3">
            <thead><tr><th>Branch</th><th class="text-right">Bookings</th><th>Revenue</th><th class="text-right">Utilisation</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td class="font-medium whitespace-nowrap">{{ $row['branch_name'] }}</td>
                        <td class="text-right">{{ $row['total_bookings'] }}</td>
                        <td class="min-w-40">
                            <div class="flex items-center gap-3">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-brand" style="width: {{ $row['revenue_period'] / $maxRevenue * 100 }}%"></div></div>
                                <span class="w-20 text-right text-xs">RM {{ number_format($row['revenue_period'], 0) }}</span>
                            </div>
                        </td>
                        <td class="text-right">{{ number_format($row['fleet_utilization'] * 100, 1) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-subtle">No branches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
