@extends('layouts.app')

@section('title', 'Booking '.$booking->reference)

@section('content')
    @php
        $steps = [
            ['Reserved', true],
            ['Paid', $booking->isPaid() || in_array($booking->payment_status->value, ['refunded', 'refund_pending'])],
            ['Confirmed', in_array($booking->status, ['approved', 'completed'])],
            ['Picked up', $booking->picked_up_at !== null],
            ['Completed', $booking->status === 'completed'],
        ];
        $outstanding = $booking->outstandingCharges();
        if (! config('payments.enabled')) { unset($steps[1]); }
        $terminal = in_array($booking->status, ['rejected', 'cancelled']);
    @endphp

    <div class="container-ec">
        <x-page-header :eyebrow="'Booking '.$booking->reference" :title="$booking->car_names" :back="route('bookings.index')">
            <x-slot:actions>
                @can('update', $booking)
                    <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-outline"><x-icon name="pencil" class="size-4" /> Change</a>
                @endcan
                @can('cancel', $booking)
                    <button type="button" class="btn btn-danger" x-data @click="$dispatch('open-modal', 'cancel-booking')"><x-icon name="x-circle" class="size-4" /> Cancel booking</button>
                @endcan
            </x-slot:actions>
        </x-page-header>

        @if (in_array($booking->status, ['pending', 'approved']) && ! $booking->picked_up_at && $booking->driverBlocker())
            @php
                $driver = auth()->user()->driverProfile;
                [$headline, $detail, $cta] = match (true) {
                    $driver === null => ['Verify your driving licence to get this booking confirmed', 'Upload your licence and IC/passport once — it takes two minutes and covers every future trip.', 'Verify now'],
                    $driver->status->value === 'pending' => ['We’re checking your driving licence', 'Our team usually verifies documents within one business day. We’ll email you, then confirm this booking.', 'View status'],
                    $driver->status->value === 'rejected' => ['Your documents need another look', $driver->rejection_reason.' — please update them so we can confirm this booking.', 'Update documents'],
                    default => ['Update your driving licence', 'Your licence on file doesn’t cover this trip (expiry or age requirement). Please upload a current licence.', 'Update licence'],
                };
            @endphp
            <a href="{{ route('verification.show') }}" class="mb-6 flex flex-col gap-3 rounded-xl bg-gradient-to-r from-sky-500/15 to-transparent p-5 ring-1 ring-sky-500/30 sm:flex-row sm:items-center">
                <x-icon name="shield" class="size-6 text-sky-300" />
                <div class="flex-1">
                    <p class="font-semibold">{{ $headline }}</p>
                    <p class="text-sm text-white/70">{{ $detail }}</p>
                </div>
                <span class="btn btn-light btn-sm">{{ $cta }}</span>
            </a>
        @endif

        {{-- Progress --}}
        <div class="card card-pad mb-8">
            @if ($terminal)
                <div class="flex items-start gap-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-full {{ $booking->isRejected() ? 'bg-red-500/15 text-red-300' : 'bg-white/5 text-mute' }}"><x-icon name="x-circle" class="size-6" /></span>
                    <div>
                        <p class="text-lg font-semibold">{{ $booking->isRejected() ? 'This booking was not approved' : 'This booking was cancelled' }}</p>
                        <p class="mt-1 text-sm text-mute">{{ $booking->isRejected() ? $booking->rejection_reason : $booking->cancellation_reason }}</p>
                        @if (in_array($booking->payment_status->value, ['refunded', 'refund_pending']))
                            <p class="mt-3 inline-flex items-center gap-2 text-sm text-sky-300"><x-icon name="cash" class="size-4" /> {{ $booking->payment_status->value === 'refunded' ? 'Your payment has been refunded.' : 'Your refund is being processed.' }} Banks can take 5–10 business days.</p>
                        @endif
                    </div>
                </div>
            @else
                <ol class="grid gap-4 {{ count($steps) === 5 ? 'sm:grid-cols-5' : 'sm:grid-cols-4' }}">
                    @foreach (array_values($steps) as $i => [$label, $done])
                        <li class="flex items-center gap-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-full text-sm font-bold {{ $done ? 'bg-brand text-white' : 'bg-white/5 text-subtle ring-1 ring-white/10' }}">
                                @if ($done)<x-icon name="check" class="size-4" />@else{{ $i + 1 }}@endif
                            </span>
                            <span class="text-sm font-semibold {{ $done ? 'text-white' : 'text-subtle' }}">{{ $label }}</span>
                            @if (! $loop->last)<span class="hidden h-px flex-1 sm:block {{ $done ? 'bg-brand/60' : 'bg-white/10' }}"></span>@endif
                        </li>
                    @endforeach
                </ol>
                @if ($booking->isOnTrip())
                    <div class="mt-5 flex flex-wrap items-center gap-3 rounded-lg px-4 py-3 text-sm ring-1 {{ $booking->isOverdue() ? 'bg-red-500/10 text-red-200 ring-red-500/30' : 'bg-sky-500/10 text-sky-100 ring-sky-500/25' }}">
                        <x-icon :name="$booking->isOverdue() ? 'warning' : 'car'" class="size-5" />
                        <span>
                            @if ($booking->isOverdue())
                                <span class="font-semibold">Your return is overdue.</span> It was due {{ $booking->returnDueAt()->format('l, d M \a\t g:i A') }} — late fees apply for each started day.
                            @else
                                <span class="font-semibold">Enjoy your trip!</span> Please return by {{ $booking->returnDueAt()->format('l, d M \a\t g:i A') }} with the same fuel level.
                            @endif
                        </span>
                    </div>
                @elseif ($booking->isCompleted())
                    <p class="mt-5 text-sm text-mute">Trip completed {{ $booking->completed_at?->format('d M Y') }}. Thanks for driving with EasyCar!</p>
                @elseif ($booking->isApproved() && $booking->approval_notes)
                    <p class="mt-5 rounded-lg bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200 ring-1 ring-emerald-500/25"><span class="font-semibold">Note from our team:</span> {{ $booking->approval_notes }}</p>
                @elseif ($booking->isPending())
                    <p class="mt-5 text-sm text-mute">Our branch team reviews bookings within one business day. You'll get an email the moment it's confirmed.</p>
                @endif
            @endif
        </div>

        <div class="grid gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(340px,1fr)]">
            <div class="space-y-6">
                {{-- Cars --}}
                <section class="card overflow-hidden">
                    @foreach ($booking->cars as $car)
                        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center {{ $loop->first ? '' : 'border-t border-line' }}">
                            <a href="{{ route('cars.show', $car) }}" class="block aspect-[16/10] w-full shrink-0 overflow-hidden rounded-lg bg-ink-700 sm:w-48"><x-car-photo :car="$car" /></a>
                            <div class="flex-1">
                                <h2 class="display text-3xl">{{ $car->name }}</h2>
                                <p class="mt-1 text-sm text-mute">{{ $car->type }} · <span class="capitalize">{{ strtolower($car->transmission) }}</span> · {{ $car->seats }} seats · <span class="font-mono">{{ $car->plate_number }}</span></p>
                                <p class="mt-2 flex items-center gap-1.5 text-sm text-mute"><x-icon name="map-pin" class="size-4 text-brand" /> Pick up &amp; return at {{ $car->branch?->name }}{{ $car->branch?->address ? ' — '.$car->branch->address : '' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold">RM {{ number_format($car->pivot->price, 2) }}</p>
                                <a href="{{ route('cars.show', $car) }}" class="mt-1 inline-flex items-center gap-1 text-xs text-mute hover:text-white"><x-icon name="cube" class="size-3.5" /> View in 3D</a>
                                @foreach (['pickup' => 'Pick-up report', 'return' => 'Return report'] as $type => $label)
                                    @if ($report = $booking->inspectionFor($car->id, $type))
                                        <a href="{{ route('inspections.show', $report) }}" class="mt-1 flex items-center justify-end gap-1 text-xs text-mute hover:text-white"><x-icon name="receipt" class="size-3.5" /> {{ $label }}</a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>

                {{-- Trip details --}}
                <section class="card card-pad">
                    <h2 class="text-lg font-semibold">Trip details</h2>
                    <dl class="mt-5 grid gap-5 sm:grid-cols-3">
                        <div><dt class="label">Pick-up</dt><dd class="text-base font-semibold">{{ $booking->start_date->format('D, d M Y') }}</dd></div>
                        <div><dt class="label">Return</dt><dd class="text-base font-semibold">{{ $booking->end_date->format('D, d M Y') }}</dd></div>
                        <div><dt class="label">Duration</dt><dd class="text-base font-semibold">{{ $booking->total_days }} {{ Str::plural('day', $booking->total_days) }}</dd></div>
                        <div><dt class="label">Reference</dt><dd class="font-mono text-base">{{ $booking->reference }}</dd></div>
                        <div><dt class="label">Booked on</dt><dd class="text-base">{{ $booking->created_at->format('d M Y, g:i A') }}</dd></div>
                        <div><dt class="label">Status</dt><dd><x-status-badge :booking="$booking" /></dd></div>
                    </dl>
                    <div class="divider mt-6 pt-5 text-sm text-mute">
                        <p class="font-semibold text-white">Rental policy</p>
                        <p class="mt-1">Pick up from {{ $booking->pickupDueAt()->format('g:i A') }} on {{ $booking->start_date->format('d M') }}; return by {{ $booking->returnDueAt()->format('g:i A') }} on {{ $booking->end_date->format('d M') }} ({{ config('rentals.late_grace_minutes') }} min grace). Late returns are charged per started day at the daily rate. Please return with the same fuel level — top-ups are RM {{ number_format(config('rentals.fuel_charge_per_eighth'), 2) }} per ⅛ tank.@if (config('rentals.daily_km_allowance')) Includes {{ config('rentals.daily_km_allowance') }} km/day; extra km RM {{ number_format(config('rentals.excess_km_rate'), 2) }} each.@endif</p>
                    </div>
                    <div class="divider mt-5 pt-5 text-sm text-mute">
                        <p class="font-semibold text-white">Bring to pick-up</p>
                        <p class="mt-1">Driving licence, IC or passport, and this reference: <span class="font-mono text-white">{{ $booking->reference }}</span>.</p>
                    </div>
                </section>

                {{-- Payment history --}}
                @if ($booking->payments->isNotEmpty())
                    <section class="card">
                        <h2 class="px-5 pt-5 text-lg font-semibold">Payment history</h2>
                        <div class="overflow-x-auto">
                            <table class="table-ec mt-3">
                                <thead><tr><th>Date</th><th>Method</th><th>Status</th><th class="text-right">Amount</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($booking->payments as $payment)
                                        <tr>
                                            <td class="text-mute">{{ $payment->created_at->format('d M Y, g:i A') }}</td>
                                            <td>{{ $payment->gatewayLabel() }}<span class="block text-xs text-subtle">{{ $payment->purposeLabel() }}</span></td>
                                            <td><x-payment-badge :status="$payment->status" />@if ($payment->failure_reason && ! $payment->isPaid())<p class="mt-1 text-xs text-subtle">{{ $payment->failure_reason }}</p>@endif</td>
                                            <td class="text-right font-semibold">RM {{ number_format($payment->amount, 2) }}</td>
                                            <td class="text-right whitespace-nowrap">
                                                @foreach ($payment->invoices as $doc)
                                                    <a href="{{ route('invoices.show', $doc) }}" class="inline-flex items-center gap-1 text-xs text-mute hover:text-white"><x-icon name="receipt" class="size-3.5" /> {{ $doc->isCreditNote() ? 'Credit note' : 'Invoice' }}</a>
                                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                @include('bookings.partials.reviews')
            </div>

            {{-- Summary + pay --}}
            <aside id="pay" class="space-y-6 lg:sticky lg:top-28 lg:self-start">
                <section class="card overflow-hidden">
                    <div class="border-b border-line p-6">
                        <p class="text-sm text-mute">Total</p>
                        <p class="display mt-1 text-5xl">RM {{ number_format($booking->total_price, 2) }}</p>
                        @include('partials.price-breakdown')
                        <div class="mt-3 flex items-center gap-2">
                            <x-payment-badge :status="$booking->payment_status" />
                            @if ($booking->isPaid() && ($paid = $booking->payments->where('purpose', 'booking')->firstWhere('status', \App\Enums\PaymentStatus::Paid)))
                                <span class="text-xs text-subtle">via {{ $paid->gatewayLabel() }} · {{ $paid->paid_at?->format('d M, g:i A') }}</span>
                            @endif
                        </div>
                    </div>

                    @if ($booking->canBePaid())
                        <form method="POST" action="{{ route('bookings.pay', $booking) }}" class="space-y-4 p-6" x-data="{ gateway: @js(array_key_exists($defaultGateway, $gateways) ? $defaultGateway : array_key_first($gateways)), busy: false }" @submit="busy = true">
                            @csrf
                            <h2 class="text-base font-semibold">Choose how to pay</h2>
                            @if (empty($gateways))
                                <p class="rounded-lg bg-amber-500/10 px-4 py-3 text-sm text-amber-200 ring-1 ring-amber-500/25">Online payment is temporarily unavailable. You can pay at the branch, or try again shortly.</p>
                            @else
                                <fieldset class="space-y-2">
                                    <legend class="sr-only">Payment method</legend>
                                    @foreach ($gateways as $key => $gw)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg p-4 ring-1 transition" :class="gateway === '{{ $key }}' ? 'bg-white/[0.04] ring-brand' : 'ring-white/10 hover:ring-white/25'">
                                            <input type="radio" name="gateway" value="{{ $key }}" x-model="gateway" class="mt-1 accent-brand">
                                            <span class="flex-1">
                                                <span class="flex items-center gap-2 text-sm font-semibold">
                                                    <x-icon :name="$key === 'sandbox' ? 'sparkles' : ($key === 'stripe' ? 'card' : 'building')" class="size-4 text-mute" /> {{ $gw['label'] }}
                                                </span>
                                                <span class="mt-0.5 block text-xs text-subtle">{{ $gw['description'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </fieldset>
                                <button class="btn btn-primary btn-lg w-full" :disabled="busy || ! gateway">
                                    <x-icon name="lock" class="size-5" />
                                    <span x-text="busy ? 'Redirecting to secure checkout…' : 'Pay RM {{ number_format($booking->total_price, 2) }}'">Pay RM {{ number_format($booking->total_price, 2) }}</span>
                                </button>
                                <p class="flex items-start gap-2 text-xs leading-relaxed text-subtle"><x-icon name="shield" class="size-4 shrink-0 text-match" /> You'll complete payment on the provider's encrypted page. EasyCar never sees or stores your card details.</p>
                                @if (config('payments.hold_minutes') > 0 && $booking->isPending())
                                    <p class="text-xs text-amber-200/80">Unpaid reservations are released {{ config('payments.hold_minutes') }} minutes after booking ({{ $booking->created_at->addMinutes(config('payments.hold_minutes'))->format('g:i A') }}).</p>
                                @endif
                            @endif
                        </form>
                    @elseif ($booking->isPaid())
                        <div class="flex items-start gap-3 p-6 text-sm text-mute">
                            <x-icon name="check-circle" class="size-5 shrink-0 text-match" />
                            <p>Paid in full. A receipt has been emailed to {{ auth()->user()->email }}.</p>
                        </div>
                    @endif
                </section>

                @if ($booking->charges->isNotEmpty())
                    <section class="card overflow-hidden" id="extras">
                        <div class="border-b border-line p-6">
                            <p class="text-sm text-mute">Extra charges</p>
                            <p class="display mt-1 text-4xl {{ $outstanding > 0 ? 'text-amber-300' : '' }}">RM {{ number_format($outstanding, 2) }} <span class="font-sans text-sm tracking-normal text-subtle">{{ $outstanding > 0 ? 'due' : 'outstanding' }}</span></p>
                            <ul class="mt-4 space-y-2 text-sm">
                                @foreach ($booking->charges as $charge)
                                    <li class="flex items-start justify-between gap-3">
                                        <span class="{{ $charge->isVoided() ? 'text-subtle line-through' : 'text-mute' }}">{{ $charge->description }}</span>
                                        <span class="flex shrink-0 items-center gap-2"><span class="font-semibold {{ $charge->isVoided() ? 'text-subtle line-through' : '' }}">RM {{ number_format($charge->amount, 2) }}</span>@php($m = $charge->statusMeta())<x-badge :tone="$m['tone']">{{ $m['label'] }}</x-badge></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @if ($booking->canPayCharges() && ! empty($gateways))
                            <form method="POST" action="{{ route('bookings.charges.pay', $booking) }}" class="space-y-3 p-6" x-data="{ busy: false }" @submit="busy = true">
                                @csrf
                                <label class="label" for="charges-gateway">Pay with</label>
                                <select id="charges-gateway" name="gateway" class="input">
                                    @foreach ($gateways as $key => $gw)<option value="{{ $key }}" @selected($key === $defaultGateway)>{{ $gw['label'] }}</option>@endforeach
                                </select>
                                <button class="btn btn-primary w-full" :disabled="busy"><x-icon name="lock" class="size-4" /> Pay RM {{ number_format($outstanding, 2) }}</button>
                                <p class="text-xs text-subtle">Questions about a charge? Call the branch before paying — we'll review it with you.</p>
                            </form>
                        @endif
                    </section>
                @endif

                <section class="card card-pad text-sm text-mute">
                    <p class="font-semibold text-white">Need a hand?</p>
                    <p class="mt-1">Call the branch{{ $booking->cars->first()?->branch?->phone ? ' on '.$booking->cars->first()->branch->phone : '' }} and quote <span class="font-mono text-white">{{ $booking->reference }}</span>.</p>
                </section>
            </aside>
        </div>
    </div>

    @can('cancel', $booking)
        <x-modal name="cancel-booking" title="Cancel this booking?">
            <form method="POST" action="{{ route('bookings.destroy', $booking) }}" class="space-y-5">
                @csrf @method('DELETE')
                <p class="text-sm text-mute">
                    {{ $booking->car_names }}, {{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M Y') }}.
                    @if ($booking->isPaid()) Your payment of <span class="font-semibold text-white">RM {{ number_format($booking->total_price, 2) }}</span> will be refunded to your original payment method. @endif
                </p>
                <x-field name="reason" label="Reason (optional)" type="textarea" rows="3" placeholder="Help us improve — why are you cancelling?" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn btn-quiet" @click="open = false">Keep booking</button>
                    <button class="btn btn-danger">Cancel booking</button>
                </div>
            </form>
        </x-modal>
    @endcan
@endsection
