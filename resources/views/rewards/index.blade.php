@extends('layouts.app')

@section('title', 'Rewards')

@section('content')
    <div class="container-ec max-w-4xl">
        <x-page-header eyebrow="EasyCar Rewards" title="Your points" subtitle="Earn points on every completed trip and use them to pay for your next one." />

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="card card-pad sm:col-span-2 bg-gradient-to-br from-amber-400/15 via-transparent to-transparent">
                <p class="text-[11px] font-semibold tracking-wider text-subtle uppercase">Balance</p>
                <p class="display mt-2 text-6xl">{{ number_format($balance) }} <span class="text-2xl text-mute">pts</span></p>
                <p class="mt-1 text-sm text-mute">Worth RM {{ number_format($value, 2) }} off your next booking.</p>
                @if ($balance < config('pricing.loyalty.min_redeem'))
                    <p class="mt-3 text-xs text-subtle">{{ number_format(config('pricing.loyalty.min_redeem') - $balance) }} more points to start redeeming.</p>
                @endif
            </div>
            <div class="card card-pad text-sm text-mute">
                <p class="font-semibold text-white">How it works</p>
                <ul class="mt-3 space-y-2">
                    <li class="flex gap-2"><x-icon name="check" class="size-4 text-match" /> {{ rtrim(rtrim(number_format(config('pricing.loyalty.points_per_ringgit'), 2), '0'), '.') }} point per RM 1 on completed trips</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-4 text-match" /> 100 points = RM {{ number_format($loyalty->valueOf(100), 2) }}</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-4 text-match" /> Use from {{ config('pricing.loyalty.min_redeem') }} points, up to {{ rtrim(rtrim(number_format(config('pricing.loyalty.max_redeem_percent'), 2), '0'), '.') }}% of a booking</li>
                    <li class="flex gap-2"><x-icon name="check" class="size-4 text-match" /> Cancelled bookings return your points</li>
                </ul>
            </div>
        </div>

        <section class="card mt-8 overflow-hidden">
            <h2 class="border-b border-line px-6 py-4 font-semibold">Activity</h2>
            <ul class="divide-y divide-line">
                @forelse ($transactions as $t)
                    <li class="flex items-center gap-4 px-6 py-3.5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $t->points >= 0 ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/5 text-mute' }}"><x-icon :name="$t->points >= 0 ? 'plus' : 'minus'" class="size-4" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm">{{ $t->description }}</p>
                            <p class="text-xs text-subtle">{{ $t->created_at->format('d M Y') }}@if ($t->booking) · <a href="{{ route('bookings.show', $t->booking) }}" class="hover:text-white">{{ $t->booking->reference }}</a>@endif</p>
                        </div>
                        <span class="font-semibold tabular-nums {{ $t->points >= 0 ? 'text-emerald-300' : 'text-mute' }}">{{ $t->points >= 0 ? '+' : '' }}{{ number_format($t->points) }}</span>
                    </li>
                @empty
                    <li><x-empty-state icon="star" title="No points yet" description="Complete a trip to earn your first points." class="border-0"><a href="{{ route('cars.index') }}" class="btn btn-primary">Browse cars</a></x-empty-state></li>
                @endforelse
            </ul>
        </section>
        <div class="mt-6">{{ $transactions->links() }}</div>
    </div>
@endsection
