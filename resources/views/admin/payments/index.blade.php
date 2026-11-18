@extends('layouts.console')

@section('title', 'Payments')
@section('breadcrumb', 'Admin console / Payments')

@section('content')
    <x-page-header eyebrow="Finance" title="Payments" subtitle="Every checkout attempt across all gateways, with refunds and webhook history." />

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Collected (all time)" :value="'RM '.number_format($summary['collected'], 0)" icon="cash" />
        <x-stat label="This month" :value="'RM '.number_format($summary['month'], 0)" icon="chart" tone="text-match" />
        <x-stat label="Refunded" :value="'RM '.number_format($summary['refunded'], 0)" icon="rotate" tone="text-sky-300" />
        <x-stat label="Refunds to action" :value="$summary['refund_pending']" icon="warning" :tone="$summary['refund_pending'] ? 'text-amber-300' : 'text-white'" hint="manual refunds pending" />
    </div>

    <div class="card card-pad mb-6">
        <p class="label">Gateways</p>
        <div class="mt-1 flex flex-wrap gap-2">
            @foreach ($gatewayOptions as $key => $label)
                @php($live = array_key_exists($key, $configured))
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold ring-1 {{ $live ? 'bg-emerald-500/10 text-emerald-200 ring-emerald-500/30' : 'bg-white/[0.03] text-subtle ring-white/10' }}">
                    <span class="size-1.5 rounded-full {{ $live ? 'bg-match' : 'bg-white/30' }}"></span> {{ $label }} · {{ $live ? 'live' : 'not configured' }}
                </span>
            @endforeach
        </div>
        <p class="help">Configure gateways with environment variables — see <span class="font-mono">config/payments.php</span>.</p>
    </div>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-[1.6fr_1fr_1fr_auto] sm:items-end">
        <label class="block"><span class="label">Search</span><input name="search" value="{{ request('search') }}" placeholder="Booking ref, transaction ID, email" class="input"></label>
        <x-field name="status" label="Status" type="select" placeholder="Any status" :value="request('status')" :options="collect(\App\Enums\PaymentStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" />
        <x-field name="gateway" label="Gateway" type="select" placeholder="Any gateway" :value="request('gateway')" :options="$gatewayOptions->all()" />
        <div class="flex gap-2"><button class="btn btn-primary">Filter</button>@if (request()->hasAny(['search', 'status', 'gateway']))<a href="{{ route('admin.payments.index') }}" class="btn btn-outline">Clear</a>@endif</div>
    </form>

    <div class="card overflow-x-auto">
        <table class="table-ec">
            <thead><tr><th>Date</th><th>Booking</th><th>Customer</th><th>Gateway</th><th>Status</th><th class="text-right">Amount</th><th></th></tr></thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td class="whitespace-nowrap text-mute">{{ $payment->created_at->format('d M Y, g:i A') }}</td>
                        <td class="font-mono text-xs">@if ($payment->booking)<a href="{{ route('admin.bookings.show', $payment->booking) }}" class="hover:underline">{{ $payment->booking->reference }}</a>@endif</td>
                        <td><p class="font-medium">{{ $payment->user?->name }}</p><p class="text-xs text-subtle">{{ $payment->user?->email }}</p></td>
                        <td class="text-mute">{{ $payment->gatewayLabel() }}</td>
                        <td><x-payment-badge :status="$payment->status" /></td>
                        <td class="text-right font-semibold whitespace-nowrap">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}@if ($payment->refunded_amount > 0)<p class="text-xs font-normal text-sky-300">−{{ number_format($payment->refunded_amount, 2) }} refunded</p>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-quiet btn-sm" aria-label="View payment"><x-icon name="chevron-right" class="size-4" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="card" title="No payments yet" description="Payments appear here as customers check out." class="border-0" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $payments->links() }}</div>
@endsection
