@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Edit Car</h2>

    @if ($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.cars.update', $car) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="brand" class="block text-sm font-medium text-gray-700">Brand:</label>
            <input type="text" id="brand" name="brand" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                   value="{{ old('brand', $car->brand) }}" required>
        </div>

        <div class="mb-4">
            <label for="model" class="block text-sm font-medium text-gray-700">Model:</label>
            <input type="text" id="model" name="model" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                   value="{{ old('model', $car->model) }}" required>
        </div>

        <div class="mb-4">
            <label for="type" class="block text-sm font-medium text-gray-700">Type:</label>
            <input type="text" id="type" name="type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                   value="{{ old('type', $car->type) }}" required>
        </div>

        <div class="mb-4">
            <label for="transmission" class="block text-sm font-medium text-gray-700">Transmission:</label>
            <input type="text" id="transmission" name="transmission" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                   value="{{ old('transmission', $car->transmission) }}" required>
        </div>

        <div class="mb-4">
            <label for="price_per_day" class="block text-sm font-medium text-gray-700">Price per Day (RM):</label>
            <input type="number" step="0.01" min="0.01" id="price_per_day" name="price_per_day"
                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                   value="{{ old('price_per_day', $car->price_per_day) }}" required>
        </div>

        <div class="mb-4">
            <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch:</label>
            <select name="branch_id" id="branch_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                <option value="">Select Branch</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $car->branch_id) == $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="photo" class="block text-sm font-medium text-gray-700">Car Photo:</label>
            @if ($car->photo)
                <img src="{{ Storage::url($car->photo) }}" alt="{{ $car->brand }} {{ $car->model }}"
                     class="mb-2" style="max-width: 200px; border-radius: 8px;">
            @endif
            <input type="file" id="photo" name="photo" class="mt-1 block w-full">
            <p class="text-sm text-gray-500 mt-1">Leave blank to keep the current photo.</p>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Update Car</button>
        <a href="{{ route('admin.cars.index') }}" class="ml-2 text-gray-600 hover:text-gray-900">Cancel</a>
    </form>
</div>
@endsection
