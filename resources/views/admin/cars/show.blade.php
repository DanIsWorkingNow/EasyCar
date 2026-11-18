@extends('layouts.console')

@section('title', $car->name)
@section('breadcrumb', 'Admin console / Fleet / '.$car->name)

@section('content')
    <x-page-header :eyebrow="$car->plate_number" :title="$car->name" :back="route('admin.cars.index')">
        <x-slot:actions>
            <a href="{{ route('cars.show', $car) }}" class="btn btn-quiet" target="_blank" rel="noopener"><x-icon name="external" class="size-4" /> Storefront</a>
            <a href="{{ route('admin.cars.edit', $car) }}" class="btn btn-outline"><x-icon name="pencil" class="size-4" /> Edit</a>
            <form method="POST" action="{{ route('admin.cars.destroy', $car) }}" data-confirm="Remove {{ $car->name }} from the fleet? This cannot be undone.">
                @csrf @method('DELETE')
                <button class="btn btn-danger"><x-icon name="trash" class="size-4" /> Delete</button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
        <div class="overflow-hidden rounded-xl bg-[radial-gradient(ellipse_at_50%_35%,#1f1f1f_0%,#0b0b0b_70%)] ring-1 ring-line">
            <x-car-viewer :car="$car" height="h-[440px]" />
        </div>
        <div class="space-y-6">
            <section class="card card-pad">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div><dt class="label">Price / day</dt><dd class="text-lg font-semibold">RM {{ number_format($car->price_per_day, 2) }}</dd></div>
                    <div><dt class="label">Status</dt><dd><x-badge :tone="['available' => 'success', 'rented' => 'info', 'maintenance' => 'warning'][$car->status] ?? 'neutral'">{{ ucfirst($car->status) }}</x-badge>@if ($car->status_changed_at)<p class="mt-1 text-xs text-subtle">for {{ $car->days_in_current_status }} days</p>@endif</dd></div>
                    <div><dt class="label">Branch</dt><dd>{{ $car->branch?->name }}</dd></div>
                    <div><dt class="label">Body / gearbox</dt><dd>{{ $car->type }} · <span class="capitalize">{{ strtolower($car->transmission) }}</span></dd></div>
                    <div><dt class="label">Seats / fuel</dt><dd>{{ $car->seats }} · {{ $car->fuel_type }}</dd></div>
                    <div><dt class="label">3D model</dt><dd>{{ $car->model_3d ? 'Custom .glb' : 'Generated' }}</dd></div>
                </dl>
            </section>
            <section class="card">
                <h2 class="px-5 pt-5 font-semibold">Recent bookings</h2>
                <ul class="mt-3 divide-y divide-line">
                    @forelse ($car->bookings as $booking)
                        <li><a href="{{ route('admin.bookings.show', $booking) }}" class="flex items-center justify-between gap-3 px-5 py-3 text-sm hover:bg-white/[0.03]">
                            <span><span class="block font-medium">{{ $booking->user?->name }}</span><span class="text-xs text-subtle">{{ $booking->start_date->format('d M') }} – {{ $booking->end_date->format('d M Y') }}</span></span>
                            <x-status-badge :booking="$booking" />
                        </a></li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-subtle">No bookings yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
@endsection
