{{-- Shared admin/staff booking detail. Params: $booking, $approveRoute, $rejectRoute, $backRoute, $paymentRoute (nullable) --}}
<x-page-header :eyebrow="'Booking '.$booking->reference" :title="$booking->car_names" :back="route($backRoute)">
    <x-slot:actions>
        @if ($booking->canBeRejected())
            <button type="button" class="btn btn-danger" x-data @click="$dispatch('open-modal', { name: 'reject-booking', action: '{{ route($rejectRoute, $booking) }}', reference: '{{ $booking->reference }}', paid: {{ $booking->isPaid() ? 'true' : 'false' }} })"><x-icon name="x-circle" class="size-4" /> Reject</button>
        @endif
        @if ($booking->canBeApproved())
            <button type="button" class="btn btn-success" x-data @click="$dispatch('open-modal', 'approve-booking')"><x-icon name="check-circle" class="size-4" /> Approve</button>
        @endif
    </x-slot:actions>
</x-page-header>

@if ($booking->isPending() && ($blocker = $booking->approvalBlocker()))
    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg bg-amber-500/10 px-4 py-3 text-sm text-amber-200 ring-1 ring-amber-500/25">
        <x-icon name="warning" class="size-5" /> {{ $blocker }}
        @if ($booking->driverBlocker() && $booking->user->driverProfile)
            <a href="{{ route('ops.verifications.show', $booking->user->driverProfile) }}" class="ml-auto font-semibold text-white underline">Review documents</a>
        @endif
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
    <div class="space-y-6">
        <section class="card overflow-hidden">
            @foreach ($booking->cars as $car)
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center {{ $loop->first ? '' : 'border-t border-line' }}">
                    <div class="aspect-[16/10] w-full shrink-0 overflow-hidden rounded-lg bg-ink-700 sm:w-40"><x-car-photo :car="$car" /></div>
                    <div class="flex-1">
                        <p class="text-lg font-semibold">{{ $car->name }}</p>
                        <p class="text-sm text-mute">{{ $car->type }} · <span class="capitalize">{{ strtolower($car->transmission) }}</span> · <span class="font-mono">{{ $car->plate_number }}</span></p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-subtle"><x-icon name="map-pin" class="size-4" /> {{ $car->branch?->name }}</p>
                    </div>
                    <p class="text-lg font-semibold">RM {{ number_format($car->pivot->price, 2) }}</p>
                </div>
            @endforeach
        </section>

        <section class="card card-pad">
            <h2 class="font-semibold">Details</h2>
            <dl class="mt-4 grid gap-5 sm:grid-cols-3">
                <div><dt class="label">Pick-up</dt><dd class="font-semibold">{{ $booking->start_date->format('D, d M Y') }}</dd></div>
                <div><dt class="label">Return</dt><dd class="font-semibold">{{ $booking->end_date->format('D, d M Y') }}</dd></div>
                <div><dt class="label">Days</dt><dd class="font-semibold">{{ $booking->total_days }}</dd></div>
                <div><dt class="label">Status</dt><dd><x-status-badge :booking="$booking" /></dd></div>
                <div><dt class="label">Payment</dt><dd><x-payment-badge :status="$booking->payment_status" /></dd></div>
                <div><dt class="label">Created</dt><dd class="text-sm">{{ $booking->created_at->format('d M Y, g:i A') }}</dd></div>
                @if ($booking->approvedBy)
                    <div><dt class="label">{{ $booking->isRejected() ? 'Rejected' : 'Approved' }} by</dt><dd class="text-sm">{{ $booking->approvedBy->name }}<br><span class="text-subtle">{{ $booking->approved_at?->format('d M Y, g:i A') }}</span></dd></div>
                @endif
            </dl>
            @foreach (['approval_notes' => 'Approval notes', 'rejection_reason' => 'Rejection reason', 'cancellation_reason' => 'Cancellation reason'] as $field => $label)
                @if ($booking->{$field})
                    <div class="divider mt-5 pt-4"><p class="label">{{ $label }}</p><p class="text-sm text-mute">{{ $booking->{$field} }}</p></div>
                @endif
            @endforeach
        </section>

        @include('partials.booking-operations')

        @if ($booking->payments->isNotEmpty())
            <section class="card">
                <h2 class="px-5 pt-5 font-semibold">Payments</h2>
                <div class="overflow-x-auto">
                    <table class="table-ec mt-3">
                        <thead><tr><th>Date</th><th>For</th><th>Gateway</th><th>Reference</th><th>Status</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                            @foreach ($booking->payments as $payment)
                                <tr>
                                    <td class="text-mute">{{ $payment->created_at->format('d M, g:i A') }}</td>
                                    <td class="text-mute">{{ $payment->purposeLabel() }}</td>
                                    <td>{{ $payment->gatewayLabel() }}@if (($payment->meta['method'] ?? null) && $payment->gateway === 'counter')<span class="block text-xs text-subtle">{{ \App\Payments\Gateways\CounterGateway::METHODS[$payment->meta['method']] ?? '' }}</span>@endif</td>
                                    <td class="font-mono text-xs">@if ($paymentRoute ?? null)<a href="{{ route($paymentRoute, $payment) }}" class="hover:underline">{{ Str::limit($payment->gateway_reference, 22) }}</a>@else{{ Str::limit($payment->gateway_reference, 22) }}@endif</td>
                                    <td><x-payment-badge :status="$payment->status" /></td>
                                    <td class="text-right font-medium">RM {{ number_format($payment->amount, 2) }}
                                        @foreach ($payment->invoices as $doc)<a href="{{ route('invoices.show', $doc) }}" class="block text-xs font-normal text-mute hover:text-white">{{ $doc->number }}</a>@endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <aside class="space-y-6">
        <section class="card card-pad">
            <h2 class="font-semibold">Customer</h2>
            <div class="mt-4 flex items-center gap-3">
                <x-avatar :user="$booking->user" size="size-11 text-sm" />
                <div class="min-w-0"><p class="truncate font-medium">{{ $booking->user->name }}</p><p class="truncate text-sm text-subtle">{{ $booking->user->email }}</p></div>
            </div>
            <p class="mt-4 text-xs text-subtle">Customer since {{ $booking->user->created_at?->format('M Y') }} · {{ $booking->user->bookings()->count() }} booking(s)</p>
            @php($driver = $booking->user->driverProfile)
            <div class="mt-4 flex items-center justify-between gap-3 border-t border-line pt-4 text-sm">
                <span class="text-subtle">Driver</span>
                @if ($driver)
                    <a href="{{ route('ops.verifications.show', $driver) }}"><x-badge :tone="$driver->status->tone()" icon="shield">{{ $driver->status->label() }}</x-badge></a>
                @else
                    <x-badge tone="neutral">Not submitted</x-badge>
                @endif
            </div>
        </section>
        <section class="card card-pad">
            <p class="text-sm text-mute">Total</p>
            <p class="display mt-1 text-5xl">RM {{ number_format($booking->total_price, 2) }}</p>
        </section>
    </aside>
</div>

@if ($booking->canBeApproved())
    <x-modal name="approve-booking" title="Approve {{ $booking->reference }}">
        <form method="POST" action="{{ route($approveRoute, $booking) }}" class="space-y-5">
            @csrf @method('PATCH')
            @unless ($booking->isPaid())
                <p class="rounded-lg bg-amber-500/10 px-4 py-3 text-sm text-amber-200 ring-1 ring-amber-500/25">This booking hasn't been paid yet.</p>
            @endunless
            <x-field name="approval_notes" label="Note to customer (optional)" type="textarea" rows="3" placeholder="e.g. Please arrive 15 minutes early for the walk-around." />
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-quiet" @click="open = false">Cancel</button>
                <button class="btn btn-success">Approve booking</button>
            </div>
        </form>
    </x-modal>
@endif

@include('partials.reject-modal')
