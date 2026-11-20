@extends('legal.layout', ['title' => 'Terms of Rental'])

@section('title', 'Terms of Rental')

@section('legal')
    @php($policy = config('rentals'))
    <section>
        <h2>1. Who can rent</h2>
        <ul>
            <li>Drivers must be at least {{ config('compliance.min_driver_age') }} years old and hold a valid driving licence for the whole rental period.</li>
            <li>We verify your licence and IC/passport before your first booking is confirmed. The person named on the booking must be the one who collects the car.</li>
        </ul>
    </section>
    <section>
        <h2>2. Bookings, payment and cancellation</h2>
        <ul>
            <li>Bookings must start at least 2 days ahead and are confirmed by our branch team. Prices are in Malaysian Ringgit and include applicable taxes.</li>
            <li>Unpaid reservations are released {{ config('payments.hold_minutes') }} minutes after booking.</li>
            <li>You can cancel free of charge before pick-up; any payment is refunded to the original payment method. If we can't confirm a booking, it is refunded automatically.</li>
        </ul>
    </section>
    <section>
        <h2>3. Pick-up and return</h2>
        <ul>
            <li>Cars are available from {{ \Carbon\Carbon::parse($policy['pickup_time'])->format('g:i A') }} on the start date and are due back by {{ \Carbon\Carbon::parse($policy['return_time'])->format('g:i A') }} on the end date, at the same branch.</li>
            <li>At pick-up and return we record the mileage, fuel level and condition with photos, and you sign the handover report. Please check it carefully.</li>
        </ul>
    </section>
    <section>
        <h2>4. Charges after your trip</h2>
        <ul>
            <li><strong>Late return:</strong> after a {{ $policy['late_grace_minutes'] }}-minute grace period, each started 24 hours is charged at the daily rate{{ $policy['late_fee_multiplier'] != 1.0 ? ' × '.$policy['late_fee_multiplier'] : '' }}.</li>
            <li><strong>Fuel:</strong> return the car with the same fuel level. Top-ups are RM {{ number_format($policy['fuel_charge_per_eighth'], 2) }} per ⅛ tank.</li>
            @if ($policy['daily_km_allowance'])
                <li><strong>Mileage:</strong> {{ $policy['daily_km_allowance'] }} km per day included; extra kilometres are RM {{ number_format($policy['excess_km_rate'], 2) }} each.</li>
            @else
                <li><strong>Mileage:</strong> unlimited.</li>
            @endif
            <li><strong>Damage and cleaning:</strong> new damage, or cleaning beyond normal use, is charged at reasonable repair or cleaning cost and itemised on your booking.</li>
        </ul>
    </section>
    <section>
        <h2>5. Using the car</h2>
        <ul>
            <li>Only drive on public roads in Peninsular Malaysia, obey traffic laws and don't smoke in the car. Traffic fines and summonses during your rental are your responsibility.</li>
            <li>Report any accident, breakdown or damage to the branch immediately.</li>
        </ul>
    </section>
@endsection
