@extends('layouts.auth')

@section('title', 'Sandbox checkout')

@section('content')
    <div class="text-center">
        <span class="badge badge-warning"><x-icon name="sparkles" class="size-3.5" /> Test mode — no real money moves</span>
        <h1 class="display mt-5 text-4xl">EasyCar Sandbox Pay</h1>
        <p class="mt-2 text-sm text-mute">Simulates a hosted payment page so you can test the full flow, including webhooks, without a real gateway.</p>
    </div>

    <div class="mt-8 rounded-lg bg-white/[0.04] p-5 ring-1 ring-white/10">
        <div class="flex items-center justify-between text-sm"><span class="text-subtle">Merchant</span><span>{{ config('app.name') }}</span></div>
        <div class="mt-2 flex items-center justify-between text-sm"><span class="text-subtle">Reference</span><span class="font-mono">{{ $payment->booking->reference }}</span></div>
        <div class="mt-4 flex items-end justify-between border-t border-white/10 pt-4"><span class="text-sm text-subtle">Amount</span><span class="display text-4xl">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</span></div>
    </div>

    <form method="POST" action="{{ route('payments.sandbox.complete', $payment) }}" class="mt-6 space-y-2" x-data="{ busy: false }" @submit="busy = true">
        @csrf
        <button name="outcome" value="success" class="btn btn-success btn-lg w-full" :class="busy && 'pointer-events-none opacity-60'"><x-icon name="check-circle" class="size-5" /> Approve payment</button>
        <div class="grid grid-cols-2 gap-2">
            <button name="outcome" value="failure" class="btn btn-danger" :class="busy && 'pointer-events-none opacity-60'">Decline</button>
            <button name="outcome" value="cancel" class="btn btn-outline" :class="busy && 'pointer-events-none opacity-60'">Cancel</button>
        </div>
    </form>
@endsection
