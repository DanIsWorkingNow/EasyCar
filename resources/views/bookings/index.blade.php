@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-3xl font-bold mb-8 text-center">My Bookings</h2>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded text-center font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @forelse($bookings as $booking)
        <div class="mb-8 rounded-lg shadow-lg border border-gray-200 overflow-hidden hover:shadow-xl transition-shadow duration-300">
            <div class="p-6 bg-gray-50">
                <h3 class="text-xl font-semibold mb-2">Booking #{{ $booking->id }}</h3>
                <p class="text-gray-700 mb-4">
                    Rental Period: <span class="font-semibold">{{ \Carbon\Carbon::parse($booking->start_date)->format('d M Y') }}</span> 
                    to <span class="font-semibold">{{ \Carbon\Carbon::parse($booking->end_date)->format('d M Y') }}</span>
                </p>
                <p class="text-gray-800 font-semibold mb-3">Booked Cars:</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($booking->cars as $car)
                        <div class="bg-white rounded shadow p-4 flex flex-col items-center">
                            {{-- Use placeholder image or your car images if available --}}
                            <img src="{{ asset('images/cars/' . strtolower(str_replace(' ', '_', $car->model)) . '.jpeg') }}" 
                                 alt="{{ $car->brand }} {{ $car->model }}" 
                                 class="w-40 h-24 object-contain mb-4" 
                                 onerror="this.onerror=null;this.src='https://via.placeholder.com/160x96?text=No+Image';">
                            
                            <h4 class="text-lg font-semibold">{{ $car->brand }} {{ $car->model }}</h4>
                            <p class="text-sm text-gray-600">{{ ucfirst($car->transmission) }} Transmission</p>
                        </div>
                    @endforeach
                    @foreach ($bookings as $booking)
    <div class="booking-item">
        <p>Booking ID: {{ $booking->id }} | From: {{ $booking->start_date }} To: {{ $booking->end_date }}</p>

        <form action="{{ route('bookings.destroy', $booking) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Cancel Booking</button>
        </form>
    </div>
@endforeach

                </div>
            </div>
        </div>
    @empty
        <p class="text-center text-gray-600 text-lg">You have no bookings yet.</p>
    @endforelse
</div>
@endsection
