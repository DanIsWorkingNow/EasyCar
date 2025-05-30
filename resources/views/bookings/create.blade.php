@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Make a Booking</h2>

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

<a href="{{ route('bookings.index') }}" 
   class="inline-block mb-4 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
   Back to My Bookings
</a>


    @if ($errors->any())
        <div class="mb-4 text-red-600">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('bookings.store') }}">
        @csrf

        <div class="mb-4">
            <label for="start_date" class="block font-semibold">Rental Start Date:</label>
            <input type="date" id="start_date" name="start_date" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label for="end_date" class="block font-semibold">Rental End Date:</label>
            <input type="date" id="end_date" name="end_date" class="w-full border rounded p-2" required>
        </div>

        <div class="mb-4">
            <label for="cars" class="block font-semibold">Select Cars (Max 2):</label>
            <select name="cars[]" id="cars" multiple class="w-full border rounded p-2" required>
                @foreach ($cars as $car)
                    <option value="{{ $car->id }}">{{ $car->brand }} {{ $car->model }} ({{ $car->transmission }})</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
            Book Now
        </button>
    </form>
</div>
@endsection
