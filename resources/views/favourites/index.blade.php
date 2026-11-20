@extends('layouts.app')

@section('title', 'My List')

@section('content')
    <div class="container-ec" x-data @favourite-toggled.window="if (! $event.detail.favourited) document.getElementById('fav-' + $event.detail.car_id)?.remove()">
        <x-page-header eyebrow="Saved for later" title="My List" subtitle="Cars you've saved. Tap the heart on any car to add or remove it." />

        @if ($cars->isEmpty())
            <x-empty-state icon="heart" title="Your list is empty" description="Save cars you like while browsing and they'll wait for you here.">
                <a href="{{ route('cars.index') }}" class="btn btn-primary">Browse cars</a>
            </x-empty-state>
        @else
            <div class="grid gap-x-4 gap-y-8 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($cars as $car)
                    <x-car-tile :car="$car" grid id="fav-{{ $car->id }}" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
