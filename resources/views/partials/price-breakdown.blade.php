{{-- Rental / extras / discount lines under a booking total. Hidden for plain car-only bookings. --}}
@if ($booking->addons->isNotEmpty() || (float) $booking->discount_amount > 0 || (float) $booking->points_discount > 0)
    <dl class="mt-4 space-y-1.5 text-sm">
        <div class="flex justify-between text-mute"><dt>Car rental · {{ $booking->total_days }} {{ Str::plural('day', $booking->total_days) }}</dt><dd>RM {{ number_format($booking->rentalAmount(), 2) }}</dd></div>
        @foreach ($booking->addons as $addon)
            <div class="flex justify-between text-mute"><dt>{{ $addon->name }}@if ($addon->quantity > 1) × {{ $addon->quantity }}@endif</dt><dd>RM {{ number_format($addon->amount, 2) }}</dd></div>
        @endforeach
        @if ((float) $booking->discount_amount > 0)
            <div class="flex justify-between text-emerald-300"><dt>Discount{{ $booking->promoCode ? ' ('.$booking->promoCode->code.')' : '' }}</dt><dd>− RM {{ number_format($booking->discount_amount, 2) }}</dd></div>
        @endif
        @if ((float) $booking->points_discount > 0)
            <div class="flex justify-between text-emerald-300"><dt>Rewards ({{ number_format($booking->points_redeemed) }} pts)</dt><dd>− RM {{ number_format($booking->points_discount, 2) }}</dd></div>
        @endif
    </dl>
@endif
@if ((float) $booking->deposit_amount > 0 && ! $booking->deposit && in_array($booking->status, ['pending', 'approved'], true))
    <p class="mt-3 flex items-start gap-2 text-xs text-sky-200/90"><x-icon name="shield" class="size-4 shrink-0" /> Refundable deposit of RM {{ number_format($booking->deposit_amount, 2) }} taken at pick-up.</p>
@elseif ($booking->deposit)
    <p class="mt-3 flex items-start gap-2 text-xs text-sky-200/90"><x-icon name="shield" class="size-4 shrink-0" /> Deposit RM {{ number_format($booking->deposit->amount, 2) }} · {{ $booking->deposit->statusLabel() }}</p>
@endif
