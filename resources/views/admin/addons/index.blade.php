@extends('layouts.console')

@section('title', 'Extras')
@section('breadcrumb', 'Admin console / Extras')

@section('content')
    <x-page-header eyebrow="Revenue" title="Extras" subtitle="Paid add-ons customers can choose while booking. Price changes apply to new bookings only.">
        <x-slot:actions><a href="{{ route('admin.addons.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> New extra</a></x-slot:actions>
    </x-page-header>

    <div class="card overflow-x-auto">
        <table class="table-ec">
            <thead><tr><th>Extra</th><th>Price</th><th class="text-right">Max qty</th><th class="text-right">Times booked</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($addons as $addon)
                    <tr>
                        <td><div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-white/[0.04] ring-1 ring-line"><x-icon :name="$addon->icon" class="size-4 text-mute" /></span><div><p class="font-medium">{{ $addon->name }}</p><p class="text-xs text-subtle">{{ $addon->description }}</p></div></div></td>
                        <td>{{ $addon->priceLabel() }}</td>
                        <td class="text-right tabular-nums">{{ $addon->max_quantity }}</td>
                        <td class="text-right tabular-nums">{{ $addon->booking_addons_count }}</td>
                        <td>@if ($addon->is_active)<x-badge tone="success">Offered</x-badge>@else<x-badge tone="neutral">Hidden</x-badge>@endif</td>
                        <td class="text-right"><a href="{{ route('admin.addons.edit', $addon) }}" class="btn btn-sm btn-outline"><x-icon name="pencil" class="size-4" /> Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state icon="sparkles" title="No extras yet" class="border-0"><a href="{{ route('admin.addons.create') }}" class="btn btn-primary">New extra</a></x-empty-state></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
