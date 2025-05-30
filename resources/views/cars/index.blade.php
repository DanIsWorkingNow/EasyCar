@extends('layouts.app')

@section('content')
<div class="bg-[#f0f4f8] min-h-screen py-8 px-6">
    <div class="max-w-7xl mx-auto">
        <h2 class="text-3xl font-bold text-center text-[#1a1a40] mb-8">Available Cars</h2>

        <form method="GET" action="{{ route('cars.index') }}" class="flex justify-center mb-6">
            <select name="branch_id" onchange="this.form.submit()"
                class="px-4 py-2 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-600">
                <option value="">-- Filter by Branch --</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($cars as $car)
            <div class="bg-white shadow-md rounded-xl overflow-hidden transition-transform hover:scale-105">
                <img src="https://source.unsplash.com/400x200/?car,{{ $car->brand }}" 
                     alt="{{ $car->model }}" 
                     class="w-full h-40 object-cover">
                <div class="p-4">
                    <h3 class="text-xl font-semibold text-[#102542]">{{ $car->model }}</h3>
                    <p class="text-sm text-gray-600 mb-2">{{ $car->type }} • {{ $car->brand }} • {{ $car->transmission }}</p>
                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full">
                        {{ $car->branch->name }}
                    </span>
                </div>
            </div>
            @empty
            <div class="col-span-3 text-center text-gray-500">
                No cars found for the selected branch.
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
