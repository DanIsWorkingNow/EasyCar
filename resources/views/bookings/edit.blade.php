@extends('layouts.app')

@section('title', 'Change booking '.$booking->reference)

@section('content')
    @php($selected = collect(old('cars', $booking->cars->pluck('id')->all()))->map(fn ($id) => (int) $id)->values())
    <div class="container-ec max-w-6xl">
        <x-page-header :eyebrow="'Booking '.$booking->reference" title="Change your booking" subtitle="Update dates or swap cars. The price is recalculated when you save." :back="route('bookings.show', $booking)" />

        <form method="POST" action="{{ route('bookings.update', $booking) }}" x-data="{ selected: @js($selected), toggle(id) { this.selected.includes(id) ? this.selected = this.selected.filter(i => i !== id) : (this.selected.length < 2 && this.selected.push(id)) } }" class="space-y-6">
            @csrf @method('PUT')

            <section class="card card-pad grid gap-4 sm:grid-cols-2">
                <x-field name="start_date" label="Pick-up date" type="date" :value="$booking->start_date->toDateString()" :min="now()->addDays(2)->toDateString()" required />
                <x-field name="end_date" label="Return date" type="date" :value="$booking->end_date->toDateString()" :min="now()->addDays(3)->toDateString()" required />
            </section>

            <section class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Cars</h2>
                    <span class="text-sm text-mute"><span x-text="selected.length"></span>/2 selected</span>
                </div>
                @error('cars')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($cars as $car)
                        <label class="group relative cursor-pointer overflow-hidden rounded-lg bg-ink-700 ring-1 transition" :class="selected.includes({{ $car->id }}) ? 'ring-2 ring-brand' : 'ring-white/[0.07] hover:ring-white/25'">
                            <input type="checkbox" name="cars[]" value="{{ $car->id }}" class="sr-only" :checked="selected.includes({{ $car->id }})" @change.prevent="toggle({{ $car->id }})" :disabled="! selected.includes({{ $car->id }}) && selected.length >= 2">
                            <div class="aspect-[16/9] overflow-hidden"><x-car-photo :car="$car" /></div>
                            <div class="flex items-center justify-between px-3 py-2.5">
                                <div>
                                    <p class="text-sm font-semibold">{{ $car->name }}</p>
                                    <p class="text-xs text-subtle">{{ $car->type }} · {{ $car->branch?->name }}</p>
                                </div>
                                <span class="text-sm font-semibold">RM {{ number_format($car->price_per_day, 0) }}<span class="text-xs font-normal text-subtle">/d</span></span>
                            </div>
                            <span class="absolute top-2 right-2 grid size-7 place-items-center rounded-full transition" :class="selected.includes({{ $car->id }}) ? 'bg-brand text-white' : 'bg-black/60 text-transparent ring-1 ring-white/40'"><x-icon name="check" class="size-4" /></span>
                        </label>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ route('bookings.show', $booking) }}" class="btn btn-quiet">Discard changes</a>
                <button class="btn btn-primary btn-lg" :disabled="selected.length === 0">Save changes</button>
            </div>
        </form>
    </div>
@endsection
