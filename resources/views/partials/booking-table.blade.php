{{--
    Shared admin/staff bookings table.
    Params: $bookings (paginator), $showRoute, $approveRoute, $rejectRoute, $bulkRoute, $filterRoute, $showPaymentFilter (bool)
--}}
<form method="GET" action="{{ route($filterRoute) }}" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1.6fr_repeat(4,1fr)_auto] lg:items-end">
    <label class="block">
        <span class="label">Search</span>
        <span class="relative block">
            <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-subtle" />
            <input name="search" value="{{ request('search') }}" placeholder="Reference, name or email" class="input pl-9">
        </span>
    </label>
    <x-field name="status" label="Status" type="select" placeholder="Any status" :value="request('status')" :options="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'completed' => 'Completed']" />
    @if ($showPaymentFilter ?? false)
        <x-field name="payment_status" label="Payment" type="select" placeholder="Any" :value="request('payment_status')" :options="collect(\App\Enums\BookingPaymentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
        <x-field name="start_date" label="Starts after" type="date" :value="request('start_date')" />
        <x-field name="end_date" label="Ends before" type="date" :value="request('end_date')" />
    @endif
    <div class="flex gap-2 {{ ($showPaymentFilter ?? false) ? '' : 'lg:col-span-4 lg:justify-end' }}">
        <button class="btn btn-primary flex-1 lg:flex-none"><x-icon name="filter" class="size-4" /> Filter</button>
        @if (request()->hasAny(['search', 'status', 'payment_status', 'start_date', 'end_date']))
            <a href="{{ route($filterRoute) }}" class="btn btn-outline">Clear</a>
        @endif
    </div>
</form>

<div class="card" x-data="{ selected: [], all: @js($bookings->getCollection()->filter->canBeApproved()->pluck('id')->values()) }">
    <form id="bulk-form" method="POST" action="{{ route($bulkRoute) }}" class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-3" x-show="selected.length" x-cloak x-transition>
        @csrf
        <template x-for="id in selected" :key="id"><input type="hidden" name="booking_ids[]" :value="id"></template>
        <span class="text-sm font-semibold"><span x-text="selected.length"></span> selected</span>
        <input name="bulk_approval_notes" placeholder="Optional note to customers" class="input max-w-sm flex-1 py-1.5">
        <button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Approve selected</button>
        <button type="button" class="btn btn-quiet btn-sm" @click="selected = []">Clear</button>
    </form>

    <div class="overflow-x-auto">
        <table class="table-ec">
            <thead>
                <tr>
                    <th class="w-10"><input type="checkbox" class="checkbox" aria-label="Select all approvable" @change="selected = $event.target.checked ? [...all] : []" :checked="all.length && selected.length === all.length"></th>
                    <th>Booking</th><th>Customer</th><th>Car(s)</th><th>Dates</th><th>Total</th><th>Status</th><th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $booking)
                    <tr>
                        <td>@if ($booking->canBeApproved())<input type="checkbox" class="checkbox" value="{{ $booking->id }}" x-model.number="selected" aria-label="Select {{ $booking->reference }}">@endif</td>
                        <td><a href="{{ route($showRoute, $booking) }}" class="font-mono text-xs text-white hover:underline">{{ $booking->reference }}</a><p class="text-xs text-subtle">{{ $booking->created_at->diffForHumans() }}</p></td>
                        <td><p class="font-medium">{{ $booking->user?->name }}</p><p class="text-xs text-subtle">{{ $booking->user?->email }}</p></td>
                        <td class="max-w-56 truncate text-mute" title="{{ $booking->car_names }}">{{ $booking->car_names }}</td>
                        <td class="whitespace-nowrap text-mute">{{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M Y') }}</td>
                        <td class="whitespace-nowrap font-medium">RM {{ number_format($booking->total_price, 2) }}</td>
                        <td><div class="flex flex-col items-start gap-1"><x-status-badge :booking="$booking" /><x-payment-badge :status="$booking->payment_status" /></div></td>
                        <td class="text-right whitespace-nowrap">
                            @if ($booking->canBeApproved())
                                <form method="POST" action="{{ route($approveRoute, $booking) }}" class="inline" data-confirm="Approve {{ $booking->reference }}?">@csrf @method('PATCH')<button class="btn btn-success btn-sm">Approve</button></form>
                            @endif
                            @if ($booking->canBeRejected())
                                <button type="button" class="btn btn-danger btn-sm" @click="$dispatch('open-modal', { name: 'reject-booking', action: '{{ route($rejectRoute, $booking) }}', reference: '{{ $booking->reference }}', paid: {{ $booking->isPaid() ? 'true' : 'false' }} })">Reject</button>
                            @endif
                            <a href="{{ route($showRoute, $booking) }}" class="btn btn-quiet btn-sm" aria-label="View {{ $booking->reference }}"><x-icon name="chevron-right" class="size-4" /></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="calendar" title="No bookings found" description="Try a different search or filter." class="border-0" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $bookings->links() }}</div>

@include('partials.reject-modal')
