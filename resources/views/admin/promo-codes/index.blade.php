@extends('layouts.console')

@section('title', 'Promotions')
@section('breadcrumb', 'Admin console / Promotions')

@section('content')
    <x-page-header eyebrow="Revenue" title="Promotions" subtitle="Promo codes customers can apply at checkout. Redemptions count only bookings that weren't cancelled or rejected.">
        <x-slot:actions><a href="{{ route('admin.promo-codes.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New code</a></x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table-ec">
            <thead><tr><th>Code</th><th>Offer</th><th>Conditions</th><th>Valid</th><th class="text-right">Redeemed</th><th class="text-right">Discount given</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($promos as $promo)
                    @php
                        $expired = $promo->ends_at && $promo->ends_at->isPast();
                        $exhausted = $promo->max_redemptions && $promo->redemptions_count >= $promo->max_redemptions;
                    @endphp
                    <tr>
                        <td><span class="font-mono font-semibold tracking-wide">{{ $promo->code }}</span>@if ($promo->description)<span class="block text-xs text-subtle">{{ $promo->description }}</span>@endif</td>
                        <td>{{ $promo->label() }}</td>
                        <td class="text-xs text-mute">
                            @if ($promo->min_spend) Min RM {{ number_format($promo->min_spend, 0) }}<br>@endif
                            {{ $promo->per_user_limit ? $promo->per_user_limit.' per customer' : 'Unlimited per customer' }}
                            @if ($promo->first_booking_only)<br>First booking only @endif
                        </td>
                        <td class="text-xs text-mute">{{ $promo->starts_at?->format('d M Y') ?? 'Now' }} – {{ $promo->ends_at?->format('d M Y') ?? 'no end' }}</td>
                        <td class="text-right tabular-nums">{{ $promo->redemptions_count }}{{ $promo->max_redemptions ? ' / '.$promo->max_redemptions : '' }}</td>
                        <td class="text-right tabular-nums">RM {{ number_format((float) $promo->discount_given, 2) }}</td>
                        <td>
                            @if (! $promo->is_active)<x-badge tone="neutral">Inactive</x-badge>
                            @elseif ($expired)<x-badge tone="neutral">Expired</x-badge>
                            @elseif ($exhausted)<x-badge tone="warning">Fully redeemed</x-badge>
                            @elseif ($promo->starts_at?->isFuture())<x-badge tone="info">Scheduled</x-badge>
                            @else<x-badge tone="success">Live</x-badge>@endif
                        </td>
                        <td class="text-right"><a href="{{ route('admin.promo-codes.edit', $promo) }}" class="btn btn-sm btn-outline"><x-icon name="pencil" class="size-4" /> Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-empty-state icon="tag" title="No promo codes yet" description="Create a code to run a campaign or reward loyal customers." class="border-0"><a href="{{ route('admin.promo-codes.create') }}" class="btn btn-primary">New code</a></x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
