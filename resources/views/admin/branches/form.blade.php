@extends('layouts.console')

@section('title', $branch->exists ? 'Edit '.$branch->name : 'Add branch')
@section('breadcrumb', 'Admin console / Branches')

@section('content')
    <x-page-header eyebrow="Network" :title="$branch->exists ? $branch->name : 'Add branch'" :back="route('admin.branches.index')" />

    <form method="POST" action="{{ $branch->exists ? route('admin.branches.update', $branch) : route('admin.branches.store') }}" class="card max-w-2xl">
        @csrf
        @if ($branch->exists) @method('PUT') @endif
        <div class="card-pad space-y-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Name" :value="$branch->name" required />
                <x-field name="city" label="City" :value="$branch->city" />
            </div>
            <x-field name="address" label="Address" :value="$branch->address" />
            <x-field name="phone" label="Phone" type="tel" :value="$branch->phone" />
        </div>
        <div class="flex items-center gap-2 border-t border-line px-6 py-4">
            @if ($branch->exists)
                <button form="delete-branch" class="btn btn-quiet text-red-300"><x-icon name="trash" class="size-4" /> Delete</button>
            @endif
            <a href="{{ route('admin.branches.index') }}" class="btn btn-quiet ml-auto">Cancel</a>
            <button class="btn btn-primary">{{ $branch->exists ? 'Save changes' : 'Create branch' }}</button>
        </div>
    </form>

    @if ($branch->exists)
        <form id="delete-branch" method="POST" action="{{ route('admin.branches.destroy', $branch) }}" data-confirm="Delete {{ $branch->name }}?">@csrf @method('DELETE')</form>
    @endif
@endsection
