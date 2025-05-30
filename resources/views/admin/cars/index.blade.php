@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 bg-white rounded shadow">
    <h2 class="text-3xl font-bold mb-6 text-center">Car Management</h2>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <a href="{{ route('admin.cars.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded mb-6 inline-block">Add New Car</a>

    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full table-auto">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Car Model</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Brand</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Transmission</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cars as $car)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $car->model }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $car->brand }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $car->transmission }}</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.cars.edit', $car) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                            |
                            <form action="{{ route('admin.cars.destroy', $car) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
