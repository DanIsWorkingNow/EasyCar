@extends('layouts.console')

@section('title', 'Branches')
@section('breadcrumb', 'Admin console / Branches')

@section('content')
    <x-page-header eyebrow="Network" title="Branches">
        <x-slot:actions><a href="{{ route('admin.branches.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Add branch</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($branches as $branch)
            <article class="card card-pad flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="display text-3xl">{{ $branch->name }}</h2><p class="text-sm text-subtle">{{ $branch->city ?? '—' }}</p></div>
                    <x-icon name="building" class="size-6 text-brand" />
                </div>
                <dl class="mt-5 space-y-2 text-sm text-mute">
                    <div class="flex gap-2"><x-icon name="map-pin" class="size-4" /><dd>{{ $branch->address ?? 'No address on file' }}</dd></div>
                    <div class="flex gap-2"><x-icon name="phone" class="size-4" /><dd>{{ $branch->phone ?? '—' }}</dd></div>
                </dl>
                <div class="mt-5 flex gap-4 border-t border-line pt-4 text-sm">
                    <a href="{{ route('admin.cars.index', ['branch_id' => $branch->id]) }}" class="text-mute hover:text-white"><span class="font-semibold text-white">{{ $branch->cars_count }}</span> cars</a>
                    <span class="text-mute"><span class="font-semibold text-white">{{ $branch->staff_count }}</span> staff</span>
                    <a href="{{ route('admin.branches.edit', $branch) }}" class="ml-auto inline-flex items-center gap-1 text-mute hover:text-white"><x-icon name="pencil" class="size-4" /> Edit</a>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3"><x-empty-state icon="building" title="No branches yet"><a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Add branch</a></x-empty-state></div>
        @endforelse
    </div>
@endsection
