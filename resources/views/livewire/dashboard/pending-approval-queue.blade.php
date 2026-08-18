<div class="bg-white rounded-lg shadow p-4">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-sm font-semibold text-gray-700">Pending Approval Queue ({{ $queue->count() }})</h2>
        @if (count($selected) > 0)
            <button wire:click="bulkApprove" wire:confirm="Approve {{ count($selected) }} selected booking(s)?"
                    class="text-sm bg-green-600 text-white px-3 py-1.5 rounded hover:bg-green-700">
                Approve Selected ({{ count($selected) }})
            </button>
        @endif
    </div>

    @if (session('dashboard_success'))
        <div class="mb-3 text-sm text-green-700 bg-green-50 border border-green-200 rounded px-3 py-2">
            {{ session('dashboard_success') }}
        </div>
    @endif
    @if (session('dashboard_error'))
        <div class="mb-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded px-3 py-2">
            {{ session('dashboard_error') }}
        </div>
    @endif

    <table class="min-w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-gray-500 uppercase border-b">
                <th class="py-2 w-8"><input type="checkbox" disabled title="Select rows below"></th>
                <th class="py-2">Customer</th>
                <th class="py-2">Car(s)</th>
                <th class="py-2">Dates</th>
                <th class="py-2 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($queue as $booking)
                <tr class="border-b last:border-0">
                    <td class="py-2"><input type="checkbox" wire:model.live="selected" value="{{ $booking->id }}"></td>
                    <td class="py-2">{{ $booking->user->name }}</td>
                    <td class="py-2">{{ $booking->cars->map(fn ($c) => "{$c->brand} {$c->model}")->implode(', ') }}</td>
                    <td class="py-2">{{ $booking->start_date->format('M d') }} – {{ $booking->end_date->format('M d, Y') }}</td>
                    <td class="py-2 text-right space-x-2">
                        <button wire:click="approve({{ $booking->id }})" wire:confirm="Approve this booking?"
                                class="text-green-700 hover:underline">Approve</button>
                        <button wire:click="openReject({{ $booking->id }})" class="text-red-600 hover:underline">Reject</button>
                    </td>
                </tr>

                @if ($rejectReasonFor === (string) $booking->id)
                    <tr class="bg-red-50">
                        <td colspan="5" class="p-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Rejection reason (required)</label>
                            <textarea wire:model="rejectReason" rows="2" class="w-full border-gray-300 rounded text-sm"></textarea>
                            @error('rejectReason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            <div class="mt-2 space-x-2">
                                <button wire:click="confirmReject" class="text-sm bg-red-600 text-white px-3 py-1 rounded">Confirm Reject</button>
                                <button wire:click="$set('rejectReasonFor', '')" class="text-sm text-gray-600">Cancel</button>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-400">Nothing pending — you're all caught up.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
