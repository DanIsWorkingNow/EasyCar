@extends('layouts.app')

@section('title', 'My bookings')

@section('content')
    <div class="container-ec">
        <x-page-header eyebrow="Your trips" title="My bookings">
            <x-slot:actions>
                <a href="{{ route('bookings.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New booking</a>
            </x-slot:actions>
        </x-page-header>

        @if ($bookings->isEmpty())
            <x-empty-state icon="calendar" title="No trips yet" description="Your next road trip is a few clicks away. Explore the fleet in 3D and book in a minute.">
                <a href="{{ route('cars.index') }}" class="btn btn-primary">Browse cars</a>
            </x-empty-state>
        @else
            @foreach (['Upcoming' => $upcoming, 'Past & cancelled' => $past] as $heading => $group)
                @continue($group->isEmpty())
                <h2 class="mt-10 mb-4 text-sm font-semibold tracking-wider text-mute uppercase first:mt-0">{{ $heading }} <span class="text-subtle">({{ $group->count() }})</span></h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($group as $booking)
                        @php($lead = $booking->cars->first())
                        <article class="card group flex flex-col overflow-hidden transition hover:border-white/20 sm:flex-row">
                            <a href="{{ route('bookings.show', $booking) }}" class="relative block aspect-[16/9] shrink-0 overflow-hidden bg-ink-700 sm:aspect-auto sm:w-56">
                                @if ($lead)<x-car-photo :car="$lead" class="transition duration-500 group-hover:scale-105" />@endif
                                @if ($booking->cars->count() > 1)
                                    <span class="absolute right-2 bottom-2 badge badge-neutral bg-black/70">+{{ $booking->cars->count() - 1 }} car</span>
                                @endif
                            </a>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-status-badge :booking="$booking" />
                                    @if (config('payments.enabled') && ! in_array($booking->status, ['rejected', 'cancelled']) || $booking->payment_status->value !== 'unpaid')
                                        <x-payment-badge :status="$booking->payment_status" />
                                    @endif
                                    <span class="ml-auto font-mono text-xs text-subtle">{{ $booking->reference }}</span>
                                </div>
                                <a href="{{ route('bookings.show', $booking) }}" class="mt-3 block">
                                    <h3 class="display text-3xl leading-none">{{ $booking->car_names }}</h3>
                                </a>
                                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-mute">
                                    <span class="flex items-center gap-1.5"><x-icon name="calendar" class="size-4" /> {{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M Y') }}</span>
                                    <span class="flex items-center gap-1.5"><x-icon name="map-pin" class="size-4" /> {{ $booking->cars->pluck('branch.name')->filter()->unique()->implode(', ') }}</span>
                                </p>
                                <div class="mt-auto flex flex-wrap items-center gap-2 pt-5">
                                    <span class="mr-auto text-lg font-semibold">RM {{ number_format($booking->total_price, 2) }}</span>
                                    @if ($booking->canBePaid())
                                        <a href="{{ route('bookings.show', $booking) }}#pay" class="btn btn-primary btn-sm"><x-icon name="card" class="size-4" /> Pay now</a>
                                    @endif
                                    <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline btn-sm">Details</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endforeach
        @endif
    </div>
@endsection
