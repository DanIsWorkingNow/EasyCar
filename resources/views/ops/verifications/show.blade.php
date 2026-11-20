@extends('layouts.console')

@section('title', 'Verify '.$profile->user->name)
@section('breadcrumb', 'Operations / Driver verifications / '.$profile->user->name)

@section('content')
    @php
        $age = $profile->ageOn(now());
        $minAge = config('compliance.min_driver_age');
    @endphp
    <x-page-header eyebrow="Driver verification" :title="$profile->user->name" :subtitle="$profile->user->email" :back="route('ops.verifications.index')">
        <x-slot:actions><x-badge :tone="$profile->status->tone()">{{ $profile->status->label() }}</x-badge></x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(300px,1fr)]">
        <section class="card card-pad">
            <h2 class="font-semibold">Documents</h2>
            <p class="mt-1 text-xs text-subtle">Compare the photos with the details on the right. Documents open in a new tab and are never cached.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach (\App\Models\DriverProfile::DOCUMENTS as $key => $label)
                    @continue(! $profile->documentPath($key))
                    @php($url = route('verification.document', [$profile, $key]))
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-lg bg-ink-700 ring-1 ring-line hover:ring-white/30">
                        @if (str_ends_with(strtolower($profile->documentPath($key)), '.pdf'))
                            <div class="grid aspect-[3/2] place-items-center text-mute"><x-icon name="receipt" class="size-10" /><span class="text-xs">PDF — open</span></div>
                        @else
                            <img src="{{ $url }}" alt="{{ $label }}" class="aspect-[3/2] w-full object-cover transition group-hover:scale-105">
                        @endif
                        <p class="px-3 py-2 text-xs font-semibold text-mute">{{ $label }}</p>
                    </a>
                @endforeach
            </div>
        </section>

        <aside class="space-y-6">
            <section class="card card-pad">
                <h2 class="font-semibold">Details provided</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-subtle">Licence no.</dt><dd class="font-mono">{{ $profile->licence_number }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-subtle">Licence expiry</dt><dd class="{{ $profile->licence_expiry->isPast() ? 'text-red-400' : '' }}">{{ $profile->licence_expiry->format('d M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-subtle">{{ \App\Models\DriverProfile::ID_TYPES[$profile->id_type] ?? 'ID' }}</dt><dd class="font-mono">{{ $profile->id_number }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-subtle">Date of birth</dt><dd>{{ $profile->date_of_birth->format('d M Y') }} <span class="{{ $age < $minAge ? 'text-red-400' : 'text-subtle' }}">({{ $age }})</span></dd></div>
                    @if ($profile->phone)<div class="flex justify-between gap-3"><dt class="text-subtle">Mobile</dt><dd>{{ $profile->phone }}</dd></div>@endif
                    @if ($profile->reviewer)<div class="flex justify-between gap-3"><dt class="text-subtle">Reviewed</dt><dd class="text-right">{{ $profile->reviewer->name }}<br><span class="text-xs text-subtle">{{ $profile->reviewed_at?->format('d M Y, g:i A') }}</span></dd></div>@endif
                </dl>
            </section>

            <form method="POST" action="{{ route('ops.verifications.update', $profile) }}" class="card card-pad space-y-4" x-data="{ decision: 'approve' }">
                @csrf @method('PUT')
                <h2 class="font-semibold">Decision</h2>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold ring-1" :class="decision === 'approve' ? 'bg-emerald-500/15 text-emerald-200 ring-emerald-500/40' : 'ring-white/10 text-mute'"><input type="radio" name="decision" value="approve" x-model="decision" class="sr-only"> <x-icon name="check-circle" class="size-4" /> Verify</label>
                    <label class="flex cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold ring-1" :class="decision === 'reject' ? 'bg-red-500/15 text-red-200 ring-red-500/40' : 'ring-white/10 text-mute'"><input type="radio" name="decision" value="reject" x-model="decision" class="sr-only"> <x-icon name="x-circle" class="size-4" /> Send back</label>
                </div>
                <div x-show="decision === 'reject'" x-cloak>
                    <x-field name="reason" label="What should the customer fix?" type="textarea" rows="3" placeholder="e.g. Licence photo is blurry — please retake in good light." />
                </div>
                <p class="text-xs text-subtle" x-show="decision === 'approve'">Confirm the name, photo and date of birth match on both documents, and the licence is valid.</p>
                <button class="btn btn-primary w-full">Save decision</button>
            </form>

            @if ($upcoming->isNotEmpty())
                <section class="card card-pad text-sm">
                    <h2 class="font-semibold">Upcoming bookings</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($upcoming as $booking)
                            <li class="flex justify-between gap-3"><span class="text-mute">{{ $booking->car_names }}</span><span class="text-subtle">{{ $booking->start_date->format('d M') }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
@endsection
