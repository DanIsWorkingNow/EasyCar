@extends('layouts.console')

@section('title', 'Fleet')
@section('breadcrumb', 'Admin console / Fleet')

@section('content')
    <x-page-header eyebrow="Fleet" title="Cars" :subtitle="$cars->count().' cars across '.$branches->count().' branches'">
        <x-slot:actions>
            <a href="{{ route('admin.cars.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add car</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-[1.6fr_1fr_1fr_auto] sm:items-end">
        <label class="block"><span class="label">Search</span><input name="search" value="{{ request('search') }}" placeholder="Brand, model or type" class="input"></label>
        <x-field name="branch_id" label="Branch" type="select" placeholder="All branches" :value="request('branch_id')" :options="$branches->pluck('name', 'id')->all()" />
        <x-field name="status" label="Status" type="select" placeholder="Any status" :value="request('status')" :options="['available' => 'Available', 'rented' => 'Rented', 'maintenance' => 'Maintenance']" />
        <div class="flex gap-2"><button class="btn btn-primary">Filter</button>@if (request()->hasAny(['search', 'branch_id', 'status']))<a href="{{ route('admin.cars.index') }}" class="btn btn-outline">Clear</a>@endif</div>
    </form>

    @if ($cars->isEmpty())
        <x-empty-state title="No cars found" description="Add your first car or clear the filters.">
            <a href="{{ route('admin.cars.create') }}" class="btn btn-primary">Add car</a>
        </x-empty-state>
    @else
        <div class="card overflow-x-auto">
            <table class="table-ec">
                <thead><tr><th>Car</th><th>Plate</th><th>Branch</th><th>Price / day</th><th>Bookings</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @foreach ($cars as $car)
                        <tr>
                            <td>
                                <a href="{{ route('admin.cars.show', $car) }}" class="flex items-center gap-3">
                                    <span class="block h-11 w-16 shrink-0 overflow-hidden rounded bg-ink-700"><x-car-photo :car="$car" /></span>
                                    <span><span class="block font-medium text-white">{{ $car->name }}</span><span class="text-xs text-subtle">{{ $car->type }} · <span class="capitalize">{{ strtolower($car->transmission) }}</span>{{ $car->model_3d ? ' · custom 3D' : '' }}</span></span>
                                </a>
                            </td>
                            <td class="font-mono text-xs">{{ $car->plate_number ?? '—' }}</td>
                            <td class="text-mute">{{ $car->branch?->name }}</td>
                            <td class="font-medium">RM {{ number_format($car->price_per_day, 2) }}</td>
                            <td class="text-mute">{{ $car->bookings_count }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <x-badge :tone="['available' => 'success', 'rented' => 'info', 'maintenance' => 'warning'][$car->status] ?? 'neutral'">{{ ucfirst($car->status) }}</x-badge>
                                    @if ($car->is_featured)<x-badge tone="brand">Featured</x-badge>@endif
                                </div>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('cars.show', $car) }}" class="btn btn-quiet btn-sm" title="View on storefront" target="_blank" rel="noopener"><x-icon name="eye" class="size-4" /></a>
                                <a href="{{ route('admin.cars.edit', $car) }}" class="btn btn-outline btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
