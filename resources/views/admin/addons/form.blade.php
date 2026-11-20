@extends('layouts.console')

@section('title', $addon->exists ? 'Edit '.$addon->name : 'New extra')
@section('breadcrumb', 'Admin console / Extras')

@section('content')
    <x-page-header eyebrow="Revenue" :title="$addon->exists ? $addon->name : 'New extra'" :back="route('admin.addons.index')" />

    <form method="POST" action="{{ $addon->exists ? route('admin.addons.update', $addon) : route('admin.addons.store') }}" class="card max-w-2xl">
        @csrf
        @if ($addon->exists) @method('PUT') @endif
        <div class="card-pad space-y-5">
            <div class="grid gap-4 sm:grid-cols-[1fr_180px]">
                <x-field name="name" label="Name" :value="$addon->name" required maxlength="80" />
                <x-field name="icon" label="Icon" type="select" :value="$addon->icon" :options="array_combine(\App\Http\Controllers\Admin\AddonController::ICONS, array_map('ucfirst', \App\Http\Controllers\Admin\AddonController::ICONS))" required />
            </div>
            <x-field name="description" label="Description" :value="$addon->description" help="Shown to customers under the name." />
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="price" label="Price (RM)" type="number" step="0.01" min="0" :value="$addon->price" required />
                <x-field name="pricing" label="Charged" type="select" :value="$addon->pricing" :options="['per_day' => 'Per day', 'per_booking' => 'Once per trip']" required />
                <x-field name="max_quantity" label="Max quantity" type="number" min="1" max="10" :value="$addon->max_quantity" required />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
                <x-field name="sort_order" label="Sort order" type="number" min="0" :value="$addon->sort_order" />
                <label class="flex items-center gap-2 pb-2.5 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $addon->is_active))> Offer at checkout</label>
            </div>
        </div>
        <div class="flex items-center gap-2 border-t border-line px-6 py-4">
            @if ($addon->exists)
                <button form="delete-addon" class="btn btn-quiet text-red-300"><x-icon name="trash" class="size-4" /> Delete</button>
            @endif
            <a href="{{ route('admin.addons.index') }}" class="btn btn-quiet ml-auto">Cancel</a>
            <button class="btn btn-primary">{{ $addon->exists ? 'Save changes' : 'Create extra' }}</button>
        </div>
    </form>

    @if ($addon->exists)
        <form id="delete-addon" method="POST" action="{{ route('admin.addons.destroy', $addon) }}" data-confirm="Delete {{ $addon->name }}? Extras on past bookings are hidden instead.">@csrf @method('DELETE')</form>
    @endif
@endsection
