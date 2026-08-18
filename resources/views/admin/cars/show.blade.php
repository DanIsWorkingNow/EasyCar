@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">{{ $car->brand }} {{ $car->model }}</h2>

    @if ($car->photo)
        <img src="{{ Storage::url($car->photo) }}" alt="{{ $car->brand }} {{ $car->model }}"
             class="mb-4" style="max-width: 320px; border-radius: 8px;">
    @endif

    <dl class="grid grid-cols-2 gap-2 text-sm">
        <dt class="font-medium text-gray-700">Type</dt>
        <dd>{{ $car->type }}</dd>

        <dt class="font-medium text-gray-700">Transmission</dt>
        <dd>{{ ucfirst($car->transmission) }}</dd>

        <dt class="font-medium text-gray-700">Price per Day</dt>
        <dd>RM {{ number_format($car->price_per_day, 2) }}</dd>

        <dt class="font-medium text-gray-700">Branch</dt>
        <dd>{{ $car->branch->name ?? 'N/A' }}</dd>
    </dl>

    <div class="mt-6">
        <a href="{{ route('admin.cars.edit', $car) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Edit</a>
        <a href="{{ route('admin.cars.index') }}" class="ml-2 text-gray-600 hover:text-gray-900">Back to list</a>
    </div>
</div>
@endsection
