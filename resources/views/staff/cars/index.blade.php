@extends('layouts.console')

@section('title', 'Branch fleet')
@section('breadcrumb', 'Staff console / Fleet')

@section('content')
    <x-page-header :eyebrow="auth()->user()->branch?->name" title="Branch fleet" :subtitle="$cars->count().' cars at your branch'" />

    @if ($cars->isEmpty())
        <x-empty-state title="No cars at this branch" description="Ask an administrator to assign cars to your branch." />
    @else
        <div class="grid gap-x-3 gap-y-8 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($cars as $car)
                <div>
                    <x-car-tile :car="$car" grid />
                    <div class="mt-3 flex items-center justify-between px-1 text-sm">
                        <span class="font-mono text-xs text-subtle">{{ $car->plate_number }}</span>
                        <x-badge :tone="['available' => 'success', 'rented' => 'info', 'maintenance' => 'warning'][$car->status] ?? 'neutral'">{{ ucfirst($car->status) }}</x-badge>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
