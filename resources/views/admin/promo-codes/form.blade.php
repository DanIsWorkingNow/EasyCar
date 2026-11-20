@extends('layouts.console')

@section('title', $promo->exists ? 'Edit '.$promo->code : 'New promo code')
@section('breadcrumb', 'Admin console / Promotions')

@section('content')
    <x-page-header eyebrow="Revenue" :title="$promo->exists ? $promo->code : 'New promo code'" :back="route('admin.promo-codes.index')" />

    <form method="POST" action="{{ $promo->exists ? route('admin.promo-codes.update', $promo) : route('admin.promo-codes.store') }}" class="card max-w-3xl" x-data="{ type: @js(old('type', $promo->type)) }">
        @csrf
        @if ($promo->exists) @method('PUT') @endif
        <div class="card-pad space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="code" label="Code" :value="$promo->code" required maxlength="40" class="[&_input]:font-mono [&_input]:uppercase" help="Letters, numbers, dashes. Customers type this at checkout." />
                <x-field name="description" label="Internal description" :value="$promo->description" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="type" label="Discount type" type="select" :value="$promo->type" :options="['percent' => 'Percentage off', 'fixed' => 'Fixed amount (RM) off']" x-model="type" required />
                <x-field name="value" label="Value" type="number" step="0.01" min="0.01" :value="$promo->value" required help="% or RM depending on type." />
                <div x-show="type === 'percent'"><x-field name="max_discount" label="Max discount (RM)" type="number" step="0.01" min="0" :value="$promo->max_discount" help="Optional cap." /></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="min_spend" label="Minimum spend (RM)" type="number" step="0.01" min="0" :value="$promo->min_spend" />
                <x-field name="max_redemptions" label="Total redemptions" type="number" min="1" :value="$promo->max_redemptions" help="Blank = unlimited." />
                <x-field name="per_user_limit" label="Uses per customer" type="number" min="0" :value="$promo->per_user_limit" required help="0 = unlimited." />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="starts_at" label="Starts" type="datetime-local" :value="$promo->starts_at?->format('Y-m-d\TH:i')" />
                <x-field name="ends_at" label="Ends" type="datetime-local" :value="$promo->ends_at?->format('Y-m-d\TH:i')" />
            </div>
            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="first_booking_only" value="0"><input type="checkbox" name="first_booking_only" value="1" class="checkbox" @checked(old('first_booking_only', $promo->first_booking_only))> First booking only</label>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $promo->is_active))> Active</label>
            </div>
        </div>
        <div class="flex items-center gap-2 border-t border-line px-6 py-4">
            @if ($promo->exists)
                <button form="delete-promo" class="btn btn-quiet text-red-300"><x-icon name="trash" class="size-4" /> Delete</button>
            @endif
            <a href="{{ route('admin.promo-codes.index') }}" class="btn btn-quiet ml-auto">Cancel</a>
            <button class="btn btn-primary">{{ $promo->exists ? 'Save changes' : 'Create code' }}</button>
        </div>
    </form>

    @if ($promo->exists)
        <form id="delete-promo" method="POST" action="{{ route('admin.promo-codes.destroy', $promo) }}" data-confirm="Delete {{ $promo->code }}? Used codes are deactivated instead.">@csrf @method('DELETE')</form>
    @endif
@endsection
