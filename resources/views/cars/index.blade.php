@extends('layouts.app')

@section('title', 'Browse cars')

@section('content')
    <div class="container-ec">
        <x-page-header eyebrow="The fleet" title="Browse cars" subtitle="Filter by date to see only what's free. Every car can be explored in 3D." />

        <form method="GET" action="{{ route('cars.index') }}" x-data x-ref="filters"
              class="card mb-10 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[1.6fr_repeat(4,1fr)_auto] lg:items-end">
            <label class="block">
                <span class="label">Search</span>
                <span class="relative block">
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-subtle" />
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Brand, model or type" class="input pl-9" @input.debounce.500ms="$refs.filters.requestSubmit()">
                </span>
            </label>
            <x-field name="type" label="Type" type="select" placeholder="All types" :value="$filters['type'] ?? ''" :options="array_combine(\App\Models\Car::TYPES, \App\Models\Car::TYPES)" x-on:change="$refs.filters.requestSubmit()" />
            <x-field name="transmission" label="Gearbox" type="select" placeholder="Any" :value="$filters['transmission'] ?? ''" :options="array_combine(\App\Models\Car::TRANSMISSIONS, \App\Models\Car::TRANSMISSIONS)" x-on:change="$refs.filters.requestSubmit()" />
            <x-field name="branch_id" label="Branch" type="select" placeholder="All branches" :value="$filters['branch_id'] ?? ''" :options="$branches->pluck('name', 'id')->all()" x-on:change="$refs.filters.requestSubmit()" />
            <x-field name="sort" label="Sort" type="select" :value="$filters['sort'] ?? 'recommended'" :options="['recommended' => 'Recommended', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name']" x-on:change="$refs.filters.requestSubmit()" />
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1 lg:flex-none"><x-icon name="filter" class="size-4" /> Apply</button>
                @if ($filtering)
                    <a href="{{ route('cars.index') }}" class="btn btn-outline" title="Clear filters">Clear</a>
                @endif
            </div>

            <details class="sm:col-span-2 lg:col-span-6" @if(!empty($filters['start_date']) || !empty($filters['max_price'])) open @endif>
                <summary class="cursor-pointer text-xs font-semibold tracking-wider text-mute uppercase hover:text-white">More filters — dates &amp; budget</summary>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <x-field name="start_date" label="Pick-up" type="date" :value="$filters['start_date'] ?? ''" :min="now()->addDays(2)->toDateString()" />
                    <x-field name="end_date" label="Return" type="date" :value="$filters['end_date'] ?? ''" :min="now()->addDays(3)->toDateString()" />
                    <x-field name="max_price" label="Max RM / day" type="number" min="0" step="10" :value="$filters['max_price'] ?? ''" placeholder="No limit" />
                </div>
            </details>
        </form>
    </div>

    @if ($filtering)
        <div class="container-ec">
            <p class="mb-5 text-sm text-mute"><span class="font-semibold text-white">{{ $cars->count() }}</span> {{ Str::plural('car', $cars->count()) }} match your filters
                @if (!empty($filters['start_date']) && !empty($filters['end_date']))
                    · available {{ \Carbon\Carbon::parse($filters['start_date'])->format('d M') }} – {{ \Carbon\Carbon::parse($filters['end_date'])->format('d M Y') }}
                @endif
            </p>
            @if ($cars->isEmpty())
                <x-empty-state icon="search" title="No cars match" description="Try widening your dates, budget or branch.">
                    <a href="{{ route('cars.index') }}" class="btn btn-outline">Clear filters</a>
                </x-empty-state>
            @else
                <div class="grid gap-x-3 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($cars as $car)
                        <x-car-tile :car="$car" grid />
                    @endforeach
                </div>
            @endif
        </div>
    @else
        {{-- Rows only where there's enough to scroll; everything is in the grid below. --}}
        <div class="space-y-12">
            @foreach ($rows->filter(fn ($group) => $group->count() >= 3) as $type => $group)
                <x-car-row :title="\App\Models\Car::pluralType($type)" :cars="$group" :href="route('cars.index', ['type' => $type])" />
            @endforeach
        </div>

        <div class="container-ec mt-14">
            <h2 class="mb-5 text-lg font-semibold sm:text-[1.35rem]">All cars <span class="text-sm font-normal text-subtle">({{ $cars->count() }})</span></h2>
            @if ($cars->isEmpty())
                <x-empty-state title="The showroom is being prepared" description="New cars are on their way. Check back soon." />
            @else
                <div class="grid gap-x-3 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                    @foreach ($cars as $car)
                        <x-car-tile :car="$car" grid />
                    @endforeach
                </div>
            @endif
        </div>
    @endif
@endsection
