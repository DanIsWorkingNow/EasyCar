<div class="bg-white rounded-lg shadow p-4">
    <h2 class="text-sm font-semibold text-gray-700 mb-2">Branch Performance Comparison</h2>

    <table class="min-w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-gray-500 uppercase border-b">
                <th class="py-2">Branch</th>
                <th class="py-2 text-right">Bookings</th>
                <th class="py-2 text-right">Revenue</th>
                <th class="py-2 text-right">Utilization</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="border-b last:border-0">
                    <td class="py-2 font-medium text-gray-900">{{ $row['branch_name'] }}</td>
                    <td class="py-2 text-right">{{ $row['total_bookings'] }}</td>
                    <td class="py-2 text-right">RM {{ number_format($row['revenue_period'], 2) }}</td>
                    <td class="py-2 text-right">{{ number_format($row['fleet_utilization'] * 100, 1) }}%</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-4 text-center text-gray-400">No branches found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
