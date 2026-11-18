@extends('layouts.app')

@section('title', 'Payment '.$payment->status->label())

@section('content')
    @php
        $booking = $payment->booking;
        $state = match (true) {
            $payment->isPaid() => ['check-circle', 'text-match', 'bg-emerald-500/10 ring-emerald-500/30', 'Payment successful', "You're all set. We've emailed your receipt and your booking is now in the queue for confirmation."],
            $payment->isPending() => ['clock', 'text-amber-300', 'bg-amber-500/10 ring-amber-500/30', 'Confirming your payment…', 'Your bank is confirming the transaction. This usually takes a few seconds — this page updates automatically.'],
            $payment->status === \App\Enums\PaymentStatus::Cancelled => ['x-circle', 'text-mute', 'bg-white/5 ring-white/15', 'Payment cancelled', 'No money was taken. Your reservation is still held — you can try again or pick another method.'],
            default => ['x-circle', 'text-red-400', 'bg-red-500/10 ring-red-500/30', 'Payment failed', $payment->failure_reason ?: 'The payment did not go through. No money was taken.'],
        };
    @endphp
    <div class="container-ec max-w-2xl py-6 text-center" @if ($payment->isPending()) x-data="paymentPoller('{{ route('payments.status', $payment) }}')" @endif>
        <span class="mx-auto grid size-20 place-items-center rounded-full ring-1 {{ $state[2] }}">
            @if ($payment->isPending())
                <span class="size-9 animate-spin rounded-full border-[3px] border-amber-300/25 border-t-amber-300"></span>
            @else
                <x-icon :name="$state[0]" class="size-10 {{ $state[1] }}" />
            @endif
        </span>
        <h1 class="display mt-6 text-5xl sm:text-6xl">{{ $state[3] }}</h1>
        <p class="mx-auto mt-3 max-w-md text-mute">{{ $state[4] }}</p>

        <div class="card mt-10 text-left">
            <dl class="divide-y divide-line text-sm">
                <div class="flex justify-between gap-4 px-6 py-4"><dt class="text-subtle">Booking</dt><dd class="font-mono">{{ $booking->reference }}</dd></div>
                <div class="flex justify-between gap-4 px-6 py-4"><dt class="text-subtle">Car(s)</dt><dd class="text-right">{{ $booking->car_names }}</dd></div>
                <div class="flex justify-between gap-4 px-6 py-4"><dt class="text-subtle">Dates</dt><dd>{{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M Y') }}</dd></div>
                <div class="flex justify-between gap-4 px-6 py-4"><dt class="text-subtle">Method</dt><dd>{{ $payment->gatewayLabel() }}</dd></div>
                <div class="flex justify-between gap-4 px-6 py-4"><dt class="text-subtle">Amount</dt><dd class="text-base font-semibold">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</dd></div>
                @if ($payment->isPaid())
                    <div class="flex justify-between gap-4 px-6 py-4"><dt class="text-subtle">Transaction</dt><dd class="font-mono text-xs break-all">{{ $payment->gateway_reference }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="mt-8 flex flex-col justify-center gap-2 sm:flex-row">
            <a href="{{ route('bookings.show', $booking) }}" class="btn {{ $payment->isPaid() ? 'btn-primary' : 'btn-outline' }} btn-lg">View booking</a>
            @if (! $payment->isPaid() && ! $payment->isPending() && $booking->canBePaid())
                <a href="{{ route('bookings.show', $booking) }}#pay" class="btn btn-primary btn-lg">Try again</a>
            @endif
        </div>
    </div>
@endsection
