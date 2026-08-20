@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Add New Car</h2>

    <form method="POST" action="{{ route('admin.cars.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label for="brand" class="block font-semibold">Brand:</label>
            <input type="text" id="brand" name="brand" class="w-full border rounded p-2" value="{{ old('brand') }}" required>
        </div>

        <div class="mb-4">
            <label for="model" class="block font-semibold">Model:</label>
            <input type="text" id="model" name="model" class="w-full border rounded p-2" value="{{ old('model') }}" required>
        </div>

        <div class="mb-4">
            <label for="type" class="block font-semibold">Car Type:</label>
            <input type="text" id="type" name="type" class="w-full border rounded p-2" value="{{ old('type') }}" required>
        </div>

        <div class="mb-4">
            <label for="transmission" class="block font-semibold">Transmission:</label>
            <input type="text" id="transmission" name="transmission" class="w-full border rounded p-2" value="{{ old('transmission') }}" required>
        </div>

        <div class="mb-4">
            <label for="plate_number" class="block font-semibold">Plate Number:</label>
            <input type="text" id="plate_number" name="plate_number" class="w-full border rounded p-2 font-mono uppercase" value="{{ old('plate_number') }}" required>
        </div>

        <div class="mb-4">
            <label for="price_per_day" class="block font-semibold">Price per Day (RM):</label>
            <input type="number" step="0.01" min="0.01" id="price_per_day" name="price_per_day" class="w-full border rounded p-2" value="{{ old('price_per_day') }}" required>
        </div>

        <div class="mb-4">
            <label for="branch_id" class="block font-semibold">Branch:</label>
            <select name="branch_id" id="branch_id" class="w-full border rounded p-2" required>
                <!-- Assuming you have branches in the database -->
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label for="photo" class="block font-semibold">Car Photo:</label>
            <input type="file" id="photo" name="photo" class="w-full border rounded p-2">
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Save Car
        </button>
    </form>
</div>
@endsection
