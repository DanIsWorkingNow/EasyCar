@extends('layouts.console')

@section('title', 'Payment')
@section('breadcrumb', 'Admin console / Payments / '.Str::limit($payment->uuid, 8, ''))

@section('content')
    <x-page-header eyebrow="Payment" :title="$payment->currency.' '.number_format($payment->amount, 2)" :subtitle="$payment->purposeLabel().' · '.$payment->gatewayLabel().' · '.$payment->created_at->format('d M Y, g:i A')" :back="route('admin.payments.index')">
        <x-slot:actions>
            @if ($payment->isPaid())
                <button type="button" class="btn btn-danger" x-data @click="$dispatch('open-modal', 'refund')"><x-icon name="rotate" class="size-4" /> Refund</button>
            @elseif ($payment->status === \App\Enums\PaymentStatus::RefundPending)
                <button type="button" class="btn btn-success" x-data @click="$dispatch('open-modal', 'mark-refunded')"><x-icon name="check" class="size-4" /> Mark refund sent</button>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(300px,1fr)]">
        <div class="space-y-6">
            <section class="card card-pad">
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div><dt class="label">Status</dt><dd><x-payment-badge :status="$payment->status" /></dd></div>
                    <div><dt class="label">Booking</dt><dd class="font-mono">@if ($payment->booking)<a href="{{ route('admin.bookings.show', $payment->booking) }}" class="hover:underline">{{ $payment->booking->reference }}</a>@endif</dd></div>
                    <div><dt class="label">Gateway reference</dt><dd class="font-mono text-xs break-all">{{ $payment->gateway_reference ?? '—' }}</dd></div>
                    <div><dt class="label">Internal ID</dt><dd class="font-mono text-xs break-all">{{ $payment->uuid }}</dd></div>
                    @if ($payment->gateway === 'counter')
                        <div><dt class="label">Taken at counter</dt><dd class="text-sm">{{ \App\Payments\Gateways\CounterGateway::METHODS[$payment->meta['method'] ?? ''] ?? '—' }} · by {{ $payment->meta['recorded_by_name'] ?? 'staff' }}</dd></div>
                    @endif
                    <div><dt class="label">Paid at</dt><dd>{{ $payment->paid_at?->format('d M Y, g:i:s A') ?? '—' }}</dd></div>
                    <div><dt class="label">Refunded</dt><dd>{{ $payment->refunded_amount > 0 ? $payment->currency.' '.number_format($payment->refunded_amount, 2).' on '.$payment->refunded_at?->format('d M Y') : '—' }}</dd></div>
                    @if ($payment->failure_reason)<div class="sm:col-span-2"><dt class="label">Failure reason</dt><dd class="text-sm text-red-300">{{ $payment->failure_reason }}</dd></div>@endif
                    @foreach (['refund_reason' => 'Refund reason', 'refund_note' => 'Refund note', 'refund_reference' => 'Refund reference'] as $key => $label)
                        @if (! empty($payment->meta[$key]))<div class="sm:col-span-2"><dt class="label">{{ $label }}</dt><dd class="text-sm text-mute">{{ $payment->meta[$key] }}</dd></div>@endif
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <h2 class="px-5 pt-5 font-semibold">Gateway events</h2>
                <p class="px-5 text-xs text-subtle">Verified notifications, logged once each (idempotent).</p>
                <ul class="mt-3 divide-y divide-line">
                    @forelse ($payment->events as $event)
                        <li class="px-5 py-3" x-data="{ open: false }">
                            <button type="button" class="flex w-full items-center justify-between gap-3 text-left text-sm" @click="open = !open">
                                <span><span class="font-mono text-xs text-white">{{ $event->type }}</span><span class="block text-xs text-subtle">{{ $event->created_at->format('d M Y, g:i:s A') }} · {{ Str::limit($event->event_id, 40) }}</span></span>
                                <x-icon name="chevron-down" class="size-4 text-subtle transition" ::class="open && 'rotate-180'" />
                            </button>
                            <pre x-show="open" x-cloak class="mt-3 max-h-72 overflow-auto rounded-md bg-black/50 p-3 text-[11px] leading-relaxed text-mute">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-subtle">No gateway events received yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="card card-pad">
                <h2 class="font-semibold">Customer</h2>
                @if ($payment->user)
                    <div class="mt-4 flex items-center gap-3"><x-avatar :user="$payment->user" size="size-10 text-sm" /><div><p class="font-medium">{{ $payment->user->name }}</p><p class="text-sm text-subtle">{{ $payment->user->email }}</p></div></div>
                @endif
            </section>
            @if ($payment->booking)
                <section class="card card-pad text-sm">
                    <h2 class="font-semibold">Booking</h2>
                    <p class="mt-3 text-mute">{{ $payment->booking->car_names }}</p>
                    <p class="text-mute">{{ $payment->booking->start_date->format('d M') }} – {{ $payment->booking->end_date->format('d M Y') }}</p>
                    <div class="mt-3"><x-status-badge :booking="$payment->booking" /></div>
                </section>
            @endif
        </aside>
    </div>

    @if ($payment->isPaid())
        <x-modal name="refund" title="Refund {{ $payment->currency }} {{ number_format($payment->amount - $payment->refunded_amount, 2) }}">
            <form method="POST" action="{{ route('admin.payments.refund', $payment) }}" class="space-y-5">
                @csrf
                <p class="text-sm text-mute">
                    @if ($supportsRefunds)
                        The refund is sent through {{ $payment->gatewayLabel() }} immediately.
                    @else
                        {{ $payment->gatewayLabel() }} has no refund API — the payment will be flagged for a manual bank transfer, then you mark it as sent.
                    @endif
                    This does not cancel the booking.
                </p>
                <x-field name="reason" label="Reason" required placeholder="e.g. Customer goodwill" />
                <div class="flex justify-end gap-2"><button type="button" class="btn btn-quiet" @click="open = false">Cancel</button><button class="btn btn-danger">Issue refund</button></div>
            </form>
        </x-modal>
    @endif

    @if ($payment->status === \App\Enums\PaymentStatus::RefundPending)
        <x-modal name="mark-refunded" title="Mark refund as sent">
            <form method="POST" action="{{ route('admin.payments.mark-refunded', $payment) }}" class="space-y-5">
                @csrf
                <p class="text-sm text-mute">Confirm you've returned {{ $payment->currency }} {{ number_format($payment->amount - $payment->refunded_amount, 2) }} to the customer outside the gateway.</p>
                <x-field name="reference" label="Bank / transfer reference (optional)" />
                <div class="flex justify-end gap-2"><button type="button" class="btn btn-quiet" @click="open = false">Cancel</button><button class="btn btn-success">Mark as refunded</button></div>
            </form>
        </x-modal>
    @endif
@endsection
