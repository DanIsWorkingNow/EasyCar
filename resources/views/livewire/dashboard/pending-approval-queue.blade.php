<div class="card">
    <div class="flex flex-wrap items-center gap-3 px-5 pt-5">
        <h2 class="font-semibold">Approval queue</h2>
        <span class="badge {{ $queue->count() ? 'badge-warning' : 'badge-neutral' }}">{{ $queue->count() }} pending</span>
        @if (count($selected) > 0)
            <button wire:click="bulkApprove" wire:confirm="Approve {{ count($selected) }} selected booking(s)?" class="btn btn-success btn-sm ml-auto">
                <x-icon name="check" class="size-4" /> Approve selected ({{ count($selected) }})
            </button>
        @endif
    </div>

    @if (session('dashboard_success'))
        <p class="mx-5 mt-4 rounded-lg bg-emerald-500/10 px-4 py-2.5 text-sm text-emerald-200 ring-1 ring-emerald-500/25">{{ session('dashboard_success') }}</p>
    @endif
    @if (session('dashboard_error'))
        <p class="mx-5 mt-4 rounded-lg bg-red-500/10 px-4 py-2.5 text-sm text-red-200 ring-1 ring-red-500/25">{{ session('dashboard_error') }}</p>
    @endif

    <div class="overflow-x-auto">
        <table class="table-ec mt-3">
            <thead>
                <tr><th class="w-10"><span class="sr-only">Select</span></th><th>Booking</th><th>Customer</th><th>Car(s)</th><th>Dates</th><th>Payment</th><th class="text-right">Actions</th></tr>
            </thead>
            <tbody>
                @forelse ($queue as $booking)
                    <tr wire:key="q-{{ $booking->id }}">
                        <td><input type="checkbox" wire:model.live="selected" value="{{ $booking->id }}" class="checkbox" aria-label="Select {{ $booking->reference }}"></td>
                        <td class="font-mono text-xs">{{ $booking->reference }}</td>
                        <td><p class="font-medium">{{ $booking->user->name }}</p><p class="text-xs text-subtle">{{ $booking->user->email }}</p></td>
                        <td class="text-mute">{{ $booking->car_names }}</td>
                        <td class="whitespace-nowrap text-mute">{{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M') }}
                            @if ($booking->days_until_start < 2)<span class="badge badge-danger ml-1">too soon</span>@endif
                        </td>
                        <td><x-payment-badge :status="$booking->payment_status" /></td>
                        <td class="text-right whitespace-nowrap">
                            <button wire:click="approve({{ $booking->id }})" wire:confirm="Approve {{ $booking->reference }}?" class="btn btn-sm btn-success">Approve</button>
                            <button wire:click="openReject({{ $booking->id }})" class="btn btn-sm btn-danger">Reject</button>
                        </td>
                    </tr>
                    @if ($rejectReasonFor === (string) $booking->id)
                        <tr class="bg-red-500/[0.04]" wire:key="r-{{ $booking->id }}">
                            <td colspan="7">
                                <label class="label" for="reject-{{ $booking->id }}">Reason for rejecting {{ $booking->reference }} (sent to the customer)</label>
                                <textarea id="reject-{{ $booking->id }}" wire:model="rejectReason" rows="2" class="input" placeholder="e.g. Car is booked for servicing on these dates."></textarea>
                                @error('rejectReason')<p class="field-error">{{ $message }}</p>@enderror
                                @if ($booking->isPaid())<p class="help">This booking is paid — rejecting it refunds the customer automatically.</p>@endif
                                <div class="mt-3 flex gap-2">
                                    <button wire:click="confirmReject" class="btn btn-sm btn-danger">Confirm rejection</button>
                                    <button wire:click="$set('rejectReasonFor', '')" class="btn btn-sm btn-quiet">Cancel</button>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-subtle"><x-icon name="check-circle" class="mx-auto mb-2 size-8 text-match" />Nothing pending — you're all caught up.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
