@extends('layouts.console')

@section('title', 'Pricing')
@section('breadcrumb', 'Admin console / Pricing')

@section('content')
    <x-page-header eyebrow="Revenue" title="Pricing" subtitle="Each day is priced from the car's base rate, then the best-matching season rule and weekend rule are applied. Changes affect new bookings only.">
        <x-slot:actions><a href="{{ route('admin.pricing.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New rule</a></x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @forelse (collect(config('pricing.duration_discounts'))->sortKeys() as $days => $percent)
            <x-stat :label="($days >= 28 ? 'Monthly' : 'Weekly').' rate · '.$days.'+ days'" :value="'−'.rtrim(rtrim(number_format($percent, 2), '0'), '.').'%'" icon="calendar" hint="Set in config/pricing.php (env)" />
        @empty
            <x-stat label="Long-rental discounts" value="Off" icon="calendar" />
        @endforelse
        <x-stat label="Deposit" :value="config('pricing.deposit.required') ? 'RM '.number_format(config('pricing.deposit.default'), 0).'+' : 'Off'" icon="shield" hint="Default; per type and per car overrides" />
    </div>

    <div class="card overflow-x-auto">
        <table class="table-ec">
            <thead><tr><th>Rule</th><th>When</th><th>Applies to</th><th class="text-right">Adjustment</th><th class="text-right">Priority</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <td class="font-medium">{{ $rule->name }}<span class="block text-xs text-subtle capitalize">{{ $rule->kind }}</span></td>
                        <td class="text-sm text-mute">
                            @if ($rule->kind === 'weekend'){{ collect($rule->days_of_week)->map(fn ($d) => \App\Models\PricingRule::DAYS[$d] ?? $d)->implode(', ') }}@endif
                            @if ($rule->starts_on || $rule->ends_on)<span class="block">{{ $rule->starts_on?->format('d M Y') ?? '…' }} – {{ $rule->ends_on?->format('d M Y') ?? '…' }}</span>@endif
                        </td>
                        <td class="text-sm text-mute">{{ $rule->scopeLabel() }}</td>
                        <td class="text-right font-semibold tabular-nums {{ $rule->adjustment_percent > 0 ? 'text-amber-300' : 'text-emerald-300' }}">{{ $rule->adjustmentLabel() }}</td>
                        <td class="text-right tabular-nums">{{ $rule->priority }}</td>
                        <td>@if (! $rule->is_active)<x-badge tone="neutral">Off</x-badge>@elseif ($rule->ends_on?->isPast())<x-badge tone="neutral">Ended</x-badge>@else<x-badge tone="success">Live</x-badge>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.pricing.edit', $rule) }}" class="btn btn-sm btn-outline"><x-icon name="pencil" class="size-4" /> Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="chart" title="No pricing rules" description="Every day costs the base rate. Add a weekend or seasonal rule to price for demand." class="border-0"><a href="{{ route('admin.pricing.create') }}" class="btn btn-primary">New rule</a></x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($previewCar)
        <section class="card card-pad mt-8">
            <form method="GET" class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Daily rate preview — next 5 weeks</h2>
                    <p class="text-sm text-mute">Base RM {{ number_format($previewCar->price_per_day, 2) }}/day. Long-rental discounts apply on top at checkout.</p>
                </div>
                <label class="block w-64"><span class="sr-only">Car</span>
                    <select name="car" class="input" onchange="this.form.submit()">
                        @foreach ($cars as $c)<option value="{{ $c->id }}" @selected($c->id === $previewCar->id)>{{ $c->name }} · {{ $c->plate_number }}</option>@endforeach
                    </select>
                </label>
            </form>
            <div class="mt-5 grid grid-cols-7 gap-1.5 text-center">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)<div class="pb-1 text-[11px] font-semibold text-subtle uppercase">{{ $dow }}</div>@endforeach
                @for ($i = 1; $i < today()->isoWeekday(); $i++)<div></div>@endfor
                @foreach ($preview as $date => $day)
                    @php
                        $diff = $day['rate'] - (float) $previewCar->price_per_day;
                        $tone = $diff > 0.004 ? 'bg-amber-500/15 text-amber-200 ring-amber-500/25' : ($diff < -0.004 ? 'bg-emerald-500/15 text-emerald-200 ring-emerald-500/25' : 'bg-white/[0.03] text-mute ring-line');
                    @endphp
                    <div class="rounded-md px-1 py-2 ring-1 {{ $tone }}" title="{{ implode(', ', $day['adjustments']) ?: 'Base rate' }}">
                        <p class="text-[10px] opacity-70">{{ \Carbon\Carbon::parse($date)->format('d M') }}</p>
                        <p class="text-xs font-semibold tabular-nums">{{ number_format($day['rate'], 0) }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
