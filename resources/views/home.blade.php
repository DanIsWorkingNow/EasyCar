@extends('layouts.app')

@section('title', 'Home')

@section('content')
    <div class="container-ec">
        <div class="mb-10 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">Welcome back</p>
                <h1 class="display mt-2 text-5xl sm:text-6xl">Hi, {{ Str::before(auth()->user()->name, ' ') }}.</h1>
            </div>
            <p class="text-sm text-mute">{{ $tripCount }} {{ Str::plural('trip', $tripCount) }} with EasyCar so far</p>
        </div>

        @if ($awaitingPayment->isNotEmpty())
            <div class="mb-8 space-y-3">
                @foreach ($awaitingPayment as $booking)
                    <div class="flex flex-col gap-4 rounded-xl bg-gradient-to-r from-brand/20 via-brand/10 to-transparent p-5 ring-1 ring-brand/40 sm:flex-row sm:items-center">
                        <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand/20"><x-icon name="card" class="size-5 text-white" /></span>
                        <div class="flex-1">
                            <p class="font-semibold">Complete payment to secure your {{ $booking->car_names }}</p>
                            <p class="text-sm text-white/70">{{ $booking->reference }} · {{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M') }} · RM {{ number_format($booking->total_price, 2) }}</p>
                        </div>
                        <a href="{{ route('bookings.show', $booking) }}#pay" class="btn btn-light">Pay now</a>
                    </div>
                @endforeach
            </div>
        @endif

        @foreach ($toReview as $booking)
            <div class="mb-8 flex flex-col gap-4 rounded-xl bg-gradient-to-r from-amber-400/15 via-amber-400/5 to-transparent p-5 ring-1 ring-amber-400/30 sm:flex-row sm:items-center">
                <span class="grid size-11 shrink-0 place-items-center rounded-full bg-amber-400/20"><x-icon name="star" solid class="size-5 text-amber-300" /></span>
                <div class="flex-1">
                    <p class="font-semibold">How was the {{ $booking->car_names }}?</p>
                    <p class="text-sm text-white/70">{{ $booking->reference }} · returned {{ $booking->completed_at->diffForHumans() }}. Rate your trip in a few seconds.</p>
                </div>
                <a href="{{ route('bookings.show', $booking) }}#reviews" class="btn btn-light">Rate your trip</a>
            </div>
        @endforeach

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(320px,1fr)]">
            @if ($nextTrip)
                @php($car = $nextTrip->cars->first())
                <a href="{{ route('bookings.show', $nextTrip) }}" class="group relative isolate block min-h-[320px] overflow-hidden rounded-2xl ring-1 ring-white/10">
                    @if ($car)<x-car-photo :car="$car" class="absolute inset-0 -z-10 transition duration-700 group-hover:scale-105" />@endif
                    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-black via-black/60 to-black/10"></div>
                    <div class="flex h-full min-h-[320px] flex-col justify-end p-6 sm:p-8">
                        <p class="text-xs font-bold tracking-[0.25em] text-white/70 uppercase">Your next trip · in {{ $nextTrip->days_until_start }} {{ Str::plural('day', $nextTrip->days_until_start) }}</p>
                        <h2 class="display mt-2 text-5xl sm:text-6xl">{{ $nextTrip->car_names }}</h2>
                        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-white/80">
                            <x-status-badge :booking="$nextTrip" />
                            <span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4" />{{ $nextTrip->start_date->format('D d M') }} – {{ $nextTrip->end_date->format('D d M') }}</span>
                            <span class="flex items-center gap-1.5"><x-icon name="map-pin" class="size-4" />{{ $car?->branch?->name }}</span>
                        </div>
                    </div>
                </a>
            @else
                <div class="relative isolate flex min-h-[320px] flex-col justify-end overflow-hidden rounded-2xl bg-gradient-to-br from-ink-700 to-ink-900 p-8 ring-1 ring-white/10">
                    <x-icon name="car" class="absolute -top-6 -right-6 -z-10 size-64 text-white/[0.03]" />
                    <h2 class="display text-5xl">No trips on the calendar</h2>
                    <p class="mt-2 max-w-md text-mute">Find your next ride — every car can be explored in 3D before you book.</p>
                    <div class="mt-6 flex gap-2"><a href="{{ route('bookings.create') }}" class="btn btn-primary">Plan a trip</a><a href="{{ route('cars.index') }}" class="btn btn-ghost">Browse cars</a></div>
                </div>
            @endif

            <div class="card flex flex-col">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-semibold">Recent activity</h2>
                    <a href="{{ route('notifications.index') }}" class="text-xs text-mute hover:text-white">View all</a>
                </div>
                <ul class="flex-1 divide-y divide-line">
                    @forelse ($notifications as $note)
                        <li><a href="{{ route('notifications.open', $note->id) }}" class="flex gap-3 px-5 py-3.5 text-sm hover:bg-white/[0.03]">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $note->read_at ? 'bg-white/15' : 'bg-brand' }}"></span>
                            <span><span class="block text-white/90">{{ $note->data['message'] ?? 'Booking update' }}</span><span class="text-xs text-subtle">{{ $note->created_at->diffForHumans() }}</span></span>
                        </a></li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-subtle">Nothing yet — updates about your bookings will appear here.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        @if ($upcoming->count() > 1)
            <h2 class="mt-12 mb-4 text-lg font-semibold">Also coming up</h2>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($upcoming->skip(1) as $booking)
                    <a href="{{ route('bookings.show', $booking) }}" class="card card-pad flex items-center justify-between gap-4 transition hover:border-white/20">
                        <div class="min-w-0"><p class="truncate font-semibold">{{ $booking->car_names }}</p><p class="text-sm text-subtle">{{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M') }}</p></div>
                        <x-status-badge :booking="$booking" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($myList->isNotEmpty())
        <div class="mt-14"><x-car-row title="My List" :cars="$myList" :href="route('favourites.index')" /></div>
    @endif

    @if ($picks->isNotEmpty())
        <div class="mt-14"><x-car-row title="Picked for you" :cars="$picks" :href="route('cars.index')" /></div>
    @endif
@endsection
