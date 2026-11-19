@extends('layouts.console')

@section('title', 'Operations')
@section('breadcrumb', 'Operations / Board')

@section('content')
    @php
        $sections = [
            'overdue' => ['Overdue returns', 'danger', 'warning', 'Chase these customers — late fees are accruing.'],
            'returns_today' => ['Returns due today', 'warning', 'arrow-left', 'Due back by '.\Carbon\Carbon::parse(config('rentals.return_time'))->format('g:i A').'.'],
            'pickups_today' => ['Ready for pick-up', 'success', 'arrow-right', 'Confirmed bookings starting today (or earlier and not yet collected).'],
            'on_trip' => ['On trip', 'info', 'car', 'Out with customers.'],
            'upcoming' => ['Upcoming pick-ups (next 3 days)', 'neutral', 'calendar', 'Prepare and clean these cars.'],
            'no_show' => ['No-shows', 'neutral', 'x-circle', 'Rental period ended without a pick-up — review and close.'],
        ];
        $user = auth()->user();
    @endphp

    <x-page-header eyebrow="Operations" title="Today at the counter" :subtitle="now()->format('l, d F Y')">
        <x-slot:actions>
            @if ($branches->isNotEmpty())
                <form method="GET" x-data>
                    <select name="branch_id" class="input w-auto" @change="$el.form.submit()" aria-label="Branch">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-8 grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-stat label="Overdue" :value="$buckets['overdue']->count()" icon="warning" :tone="$buckets['overdue']->isNotEmpty() ? 'text-red-400' : 'text-white'" />
        <x-stat label="Returns today" :value="$buckets['returns_today']->count()" icon="arrow-left" tone="text-amber-300" />
        <x-stat label="Pick-ups today" :value="$buckets['pickups_today']->count()" icon="arrow-right" tone="text-match" />
        <x-stat label="On trip" :value="$buckets['on_trip']->count() + $buckets['returns_today']->count() + $buckets['overdue']->count()" icon="car" tone="text-sky-300" />
    </div>

    <div class="space-y-6">
        @foreach ($sections as $key => [$title, $tone, $icon, $hint])
            @continue($buckets[$key]->isEmpty() && in_array($key, ['overdue', 'no_show']))
            <section class="card">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4">
                    <x-icon :name="$icon" class="size-5 {{ ['danger' => 'text-red-400', 'warning' => 'text-amber-300', 'success' => 'text-match', 'info' => 'text-sky-300', 'neutral' => 'text-subtle'][$tone] }}" />
                    <h2 class="font-semibold">{{ $title }}</h2>
                    <x-badge :tone="$tone">{{ $buckets[$key]->count() }}</x-badge>
                    <p class="text-xs text-subtle sm:ml-auto">{{ $hint }}</p>
                </div>
                @if ($buckets[$key]->isEmpty())
                    <p class="px-5 py-6 text-sm text-subtle">Nothing here.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($buckets[$key] as ['booking' => $booking, 'car' => $car])
                            <li class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center">
                                <div class="flex min-w-0 flex-1 items-center gap-4">
                                    <div class="h-14 w-20 shrink-0 overflow-hidden rounded-md bg-ink-700"><x-car-photo :car="$car" /></div>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold">{{ $car->name }} <span class="ml-1 font-mono text-xs font-normal text-subtle">{{ $car->plate_number }}</span></p>
                                        <p class="truncate text-sm text-mute">{{ $booking->user->name }} · <span class="font-mono text-xs">{{ $booking->reference }}</span>@if ($branchId === null && $user->isAdmin()) · {{ $car->branch?->name }}@endif</p>
                                    </div>
                                </div>
                                <div class="text-sm text-mute lg:w-56">
                                    @if (in_array($key, ['overdue', 'returns_today', 'on_trip']))
                                        Due <span class="{{ $key === 'overdue' ? 'font-semibold text-red-300' : 'text-white' }}">{{ $booking->returnDueAt()->format('D d M, g:i A') }}</span>
                                        @if ($key === 'overdue')<span class="block text-xs text-red-300/80">{{ $booking->returnDueAt()->diffForHumans(['parts' => 2]) }}</span>@endif
                                    @else
                                        {{ $booking->start_date->format('D d M') }} – {{ $booking->end_date->format('D d M') }}
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 lg:w-40"><x-payment-badge :status="$booking->payment_status" /></div>
                                <div class="flex flex-wrap gap-2 lg:justify-end">
                                    @can('handover', $car)
                                        @if (in_array($key, ['pickups_today']))
                                            <a href="{{ route('ops.pickup', [$booking, $car]) }}" class="btn btn-primary btn-sm"><x-icon name="arrow-right" class="size-4" /> Start pick-up</a>
                                        @elseif (in_array($key, ['overdue', 'returns_today', 'on_trip']))
                                            <a href="{{ route('ops.return', [$booking, $car]) }}" class="btn {{ $key === 'on_trip' ? 'btn-outline' : 'btn-primary' }} btn-sm"><x-icon name="arrow-left" class="size-4" /> Check in return</a>
                                        @endif
                                    @endcan
                                    <a href="{{ route($user->isAdmin() ? 'admin.bookings.show' : 'staff.bookings.show', $booking) }}" class="btn btn-quiet btn-sm" aria-label="Open booking {{ $booking->reference }}"><x-icon name="chevron-right" class="size-4" /></a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endforeach
    </div>
@endsection
