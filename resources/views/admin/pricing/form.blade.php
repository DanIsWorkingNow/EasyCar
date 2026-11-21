@extends('layouts.console')

@section('title', $rule->exists ? 'Edit '.$rule->name : 'New pricing rule')
@section('breadcrumb', 'Admin console / Pricing')

@section('content')
    <x-page-header eyebrow="Revenue" :title="$rule->exists ? $rule->name : 'New pricing rule'" :back="route('admin.pricing.index')" />

    @php $days = collect(old('days_of_week', $rule->days_of_week ?? []))->map(fn ($d) => (int) $d)->all(); @endphp
    <form method="POST" action="{{ $rule->exists ? route('admin.pricing.update', $rule) : route('admin.pricing.store') }}" class="card max-w-3xl" x-data="{ kind: @js(old('kind', $rule->kind)) }">
        @csrf
        @if ($rule->exists) @method('PUT') @endif
        <div class="card-pad space-y-5">
            <div class="grid gap-4 sm:grid-cols-[1fr_200px]">
                <x-field name="name" label="Name" :value="$rule->name" required maxlength="80" placeholder="e.g. School holidays, Weekend" />
                <x-field name="kind" label="Type" type="select" :value="$rule->kind" :options="['weekend' => 'Day of week', 'season' => 'Season (dates)']" x-model="kind" required />
            </div>

            <fieldset x-show="kind === 'weekend'">
                <legend class="label">Days</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\PricingRule::DAYS as $n => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm ring-1 ring-line has-[:checked]:bg-white/10 has-[:checked]:ring-white/40">
                            <input type="checkbox" name="days_of_week[]" value="{{ $n }}" class="checkbox" @checked(in_array($n, $days, true))> {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('days_of_week')<p class="field-error">{{ $message }}</p>@enderror
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="starts_on" label="From" type="date" :value="$rule->starts_on?->toDateString()" help="Optional for day-of-week rules." />
                <x-field name="ends_on" label="To" type="date" :value="$rule->ends_on?->toDateString()" />
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="adjustment_percent" label="Adjustment (%)" type="number" step="0.01" min="-90" max="300" :value="$rule->adjustment_percent" required help="+15 = 15% dearer, −10 = 10% cheaper." />
                <x-field name="car_type" label="Car type" type="select" placeholder="All types" :value="$rule->car_type" :options="array_combine(\App\Models\Car::TYPES, \App\Models\Car::TYPES)" />
                <x-field name="branch_id" label="Branch" type="select" placeholder="All branches" :value="$rule->branch_id" :options="$branches->pluck('name', 'id')->all()" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 sm:items-end">
                <x-field name="priority" label="Priority" type="number" min="-100" max="100" :value="$rule->priority ?? 0" help="When two rules of the same type match a day, the higher priority wins." />
                <label class="flex items-center gap-2 pb-2.5 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $rule->is_active))> Active</label>
            </div>
        </div>
        <div class="flex items-center gap-2 border-t border-line px-6 py-4">
            @if ($rule->exists)
                <button form="delete-rule" class="btn btn-quiet text-red-300"><x-icon name="trash" class="size-4" /> Delete</button>
            @endif
            <a href="{{ route('admin.pricing.index') }}" class="btn btn-quiet ml-auto">Cancel</a>
            <button class="btn btn-primary">{{ $rule->exists ? 'Save changes' : 'Create rule' }}</button>
        </div>
    </form>

    @if ($rule->exists)
        <form id="delete-rule" method="POST" action="{{ route('admin.pricing.destroy', $rule) }}" data-confirm="Delete {{ $rule->name }}? Existing bookings keep their price.">@csrf @method('DELETE')</form>
    @endif
@endsection
