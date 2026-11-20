{{-- Trip & handover + extra charges, for the admin/staff booking page. Params: $booking --}}
@php
    $ops = app(\App\Services\Rentals\RentalOperationsService::class);
    $trip = $booking->tripMeta();
    $outstanding = $booking->outstandingCharges();
@endphp

@if (in_array($booking->status, ['approved', 'completed']))
    <section class="card">
        <div class="flex flex-wrap items-center gap-3 px-5 pt-5">
            <h2 class="font-semibold">Trip &amp; handover</h2>
            @if ($trip)<x-badge :tone="$trip['tone']">{{ $trip['label'] }}</x-badge>@endif
            <span class="text-xs text-subtle sm:ml-auto">Pick-up {{ $booking->pickupDueAt()->format('d M, g:i A') }} · Due back {{ $booking->returnDueAt()->format('d M, g:i A') }}</span>
        </div>
        <ul class="mt-3 divide-y divide-line">
            @foreach ($booking->cars as $car)
                @php
                    $pickupReport = $booking->inspectionFor($car->id, 'pickup');
                    $returnReport = $booking->inspectionFor($car->id, 'return');
                @endphp
                <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ $car->name }} <span class="ml-1 font-mono text-xs text-subtle">{{ $car->plate_number }}</span></p>
                        <p class="text-xs text-subtle">{{ $car->branch?->name }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($pickupReport)
                            <a href="{{ route('inspections.show', $pickupReport) }}" class="btn btn-quiet btn-sm"><x-icon name="check-circle" class="size-4 text-match" /> Picked up {{ $pickupReport->performed_at->format('d M, g:i A') }}</a>
                        @elseif (! $ops->pickupBlocker($booking, $car) && Gate::allows('handover', $car))
                            <a href="{{ route('ops.pickup', [$booking, $car]) }}" class="btn btn-primary btn-sm"><x-icon name="arrow-right" class="size-4" /> Start pick-up</a>
                        @else
                            <span class="text-xs text-subtle">{{ $ops->pickupBlocker($booking, $car) ?? 'Handled by '.$car->branch?->name }}</span>
                        @endif

                        @if ($returnReport)
                            <a href="{{ route('inspections.show', $returnReport) }}" class="btn btn-quiet btn-sm"><x-icon name="check-circle" class="size-4 text-match" /> Returned {{ $returnReport->performed_at->format('d M, g:i A') }}</a>
                        @elseif ($pickupReport && Gate::allows('handover', $car))
                            <a href="{{ route('ops.return', [$booking, $car]) }}" class="btn btn-primary btn-sm"><x-icon name="arrow-left" class="size-4" /> Check in return</a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif

@if ($booking->charges->isNotEmpty() || $booking->picked_up_at)
    <section class="card" x-data="{ adding: false }">
        <div class="flex flex-wrap items-center gap-3 px-5 pt-5">
            <h2 class="font-semibold">Extra charges</h2>
            @if ($outstanding > 0)<x-badge tone="warning">RM {{ number_format($outstanding, 2) }} due</x-badge>@endif
            @can('manage-charges', $booking)
                <button type="button" class="btn btn-outline btn-sm sm:ml-auto" @click="adding = !adding"><x-icon name="plus" class="size-4" /> Add charge</button>
            @endcan
        </div>

        @can('manage-charges', $booking)
            <form method="POST" action="{{ route('ops.charges.store', $booking) }}" x-show="adding" x-cloak class="mx-5 mt-4 grid gap-3 rounded-lg bg-white/[0.03] p-4 ring-1 ring-line sm:grid-cols-[140px_1fr_120px_auto] sm:items-end">
                @csrf
                <x-field name="type" label="Type" type="select" :options="\App\Enums\ChargeType::manualOptions()" />
                <x-field name="description" label="Description" required placeholder="e.g. Cigarette smell — deep clean" />
                <x-field name="amount" label="Amount (RM)" type="number" step="0.01" min="0.01" required />
                <button class="btn btn-primary">Add</button>
                @if ($booking->cars->count() > 1)
                    <x-field name="car_id" label="Car" type="select" placeholder="Whole booking" :options="$booking->cars->pluck('name', 'id')->all()" class="sm:col-span-4" />
                @endif
            </form>
        @endcan

        <div class="overflow-x-auto">
            <table class="table-ec mt-3">
                <thead><tr><th>Charge</th><th>Status</th><th class="text-right">Amount</th><th></th></tr></thead>
                <tbody>
                    @forelse ($booking->charges as $charge)
                        <tr x-data="{ waiving: false }">
                            <td>
                                <p class="{{ $charge->isVoided() ? 'text-subtle line-through' : '' }}">{{ $charge->description }}</p>
                                <p class="text-xs text-subtle">{{ $charge->type->label() }} · {{ $charge->created_at->format('d M, g:i A') }}@if ($charge->void_reason) · {{ $charge->void_reason }}@endif</p>
                                @can('manage-charges', $booking)
                                    <form method="POST" action="{{ route('ops.charges.void', $charge) }}" x-show="waiving" x-cloak class="mt-2 flex gap-2">
                                        @csrf
                                        <input name="reason" required maxlength="200" placeholder="Reason for waiving" class="input py-1.5">
                                        <button class="btn btn-danger btn-sm">Waive</button>
                                    </form>
                                @endcan
                            </td>
                            <td><x-badge :tone="$charge->statusMeta()['tone']">{{ $charge->statusMeta()['label'] }}</x-badge></td>
                            <td class="text-right font-medium whitespace-nowrap">RM {{ number_format($charge->amount, 2) }}</td>
                            <td class="text-right">
                                @if (! $charge->isPaid() && ! $charge->isVoided())
                                    @can('manage-charges', $booking)
                                        <button type="button" class="btn btn-quiet btn-sm" @click="waiving = !waiving">Waive</button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-subtle">No extra charges.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($outstanding > 0)
            @can('manage-charges', $booking)
                <form method="POST" action="{{ route('ops.charges.collect', $booking) }}" class="flex flex-wrap items-end gap-3 border-t border-line px-5 py-4">
                    @csrf
                    <x-field name="method" label="Collected at counter by" type="select" :options="\App\Payments\Gateways\CounterGateway::METHODS" />
                    <x-field name="reference" label="Receipt / code" placeholder="Optional" />
                    <button class="btn btn-success" data-confirm="Record RM {{ number_format($outstanding, 2) }} as paid at the counter?"><x-icon name="cash" class="size-4" /> Record RM {{ number_format($outstanding, 2) }} paid</button>
                </form>
            @endcan
        @endif
    </section>
@endif

@if ($booking->deposit || ((float) $booking->deposit_amount > 0 && config('pricing.deposit.required') && $booking->isApproved()))
    @php
        $deposit = $booking->deposit;
        $applicable = $deposit ? app(\App\Services\Pricing\DepositService::class)->applicableAmount($booking) : 0;
    @endphp
    <section class="card" id="deposit">
        <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
            <h2 class="flex items-center gap-2 font-semibold"><x-icon name="shield" class="size-5 text-sky-300" /> Security deposit</h2>
            @if ($deposit)
                <x-badge :tone="$deposit->isHeld() ? 'info' : 'success'">{{ $deposit->statusLabel() }}</x-badge>
            @else
                <x-badge tone="neutral">Due at pick-up</x-badge>
            @endif
        </div>
        <div class="px-5 py-4 text-sm">
            @if (! $deposit)
                <p class="text-mute">RM {{ number_format($booking->deposit_amount, 2) }} will be taken at the counter when the car is handed over.</p>
            @else
                <dl class="grid gap-3 sm:grid-cols-3">
                    <div><dt class="label">Amount</dt><dd class="font-semibold">RM {{ number_format($deposit->amount, 2) }}</dd></div>
                    <div><dt class="label">Taken</dt><dd>{{ $deposit->methodLabel() }}<span class="block text-xs text-subtle">{{ $deposit->collected_at->format('d M, g:i A') }}{{ $deposit->reference ? ' · '.$deposit->reference : '' }}</span></dd></div>
                    @unless ($deposit->isHeld())
                        <div><dt class="label">Settled</dt><dd>
                            @if ($deposit->applied_amount > 0) RM {{ number_format($deposit->applied_amount, 2) }} to charges · @endif
                            RM {{ number_format($deposit->releasedAmount(), 2) }} released
                            <span class="block text-xs text-subtle">{{ $deposit->settled_at?->format('d M, g:i A') }}{{ $deposit->settler ? ' · '.$deposit->settler->name : '' }}</span>
                        </dd></div>
                    @endunless
                </dl>
            @endif
        </div>
        @if ($deposit?->isHeld() && ! $booking->isOnTrip() && $booking->picked_up_at)
            @can('manage-charges', $booking)
                <div class="flex flex-wrap items-center gap-2 border-t border-line px-5 py-4">
                    @if ($applicable > 0)
                        <form method="POST" action="{{ route('ops.deposit.settle', $booking) }}">
                            @csrf <input type="hidden" name="action" value="apply">
                            <button class="btn btn-success" data-confirm="Use RM {{ number_format($applicable, 2) }} of the deposit for the extra charges and release RM {{ number_format($deposit->amount - $applicable, 2) }}?"><x-icon name="receipt" class="size-4" /> Use for charges (RM {{ number_format($applicable, 2) }}) &amp; release rest</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('ops.deposit.settle', $booking) }}">
                        @csrf <input type="hidden" name="action" value="release">
                        <button class="btn {{ $applicable > 0 ? 'btn-outline' : 'btn-primary' }}" data-confirm="Release the full RM {{ number_format($deposit->amount, 2) }} deposit to the customer?"><x-icon name="check" class="size-4" /> Release full deposit</button>
                    </form>
                    @if ($booking->outstandingCharges() > 0 && $applicable <= 0)
                        <p class="w-full text-xs text-amber-300">Extra charges exceed the deposit: collect them above, then release the deposit.</p>
                    @endif
                </div>
            @endcan
        @endif
    </section>
@endif
