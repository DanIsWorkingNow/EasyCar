@extends('layouts.app')

@section('title', 'Driver verification')

@section('content')
    @php($status = $profile?->status)
    <div class="container-ec max-w-4xl">
        <x-page-header eyebrow="Account" title="Driver verification" subtitle="We verify every driver once. It keeps our cars insured and makes pick-up quicker." />

        @if ($profile)
            <div class="card card-pad mb-6 flex items-start gap-4 {{ $profile->isVerified() ? 'border-emerald-500/30' : ($status->value === 'rejected' ? 'border-red-500/30' : 'border-amber-500/30') }}">
                <x-icon :name="$profile->isVerified() ? 'shield' : ($status->value === 'rejected' ? 'warning' : 'clock')" class="mt-0.5 size-6 {{ $profile->isVerified() ? 'text-match' : ($status->value === 'rejected' ? 'text-red-400' : 'text-amber-300') }}" />
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2"><p class="font-semibold">{{ $status->label() }}</p><x-badge :tone="$status->tone()">{{ $status->label() }}</x-badge></div>
                    <p class="mt-1 text-sm text-mute">
                        @if ($profile->isVerified())
                            Verified {{ $profile->reviewed_at?->format('d M Y') }}. Licence valid until {{ $profile->licence_expiry->format('d M Y') }}.
                        @elseif ($status->value === 'rejected')
                            {{ $profile->rejection_reason }} — please update the details or upload clearer photos below.
                        @else
                            Submitted {{ $profile->submitted_at->diffForHumans() }}. We usually review within one business day and will email you.
                        @endif
                    </p>
                    @if ($profile->isVerified() && $profile->licence_expiry->lt(now()->addDays(60)))
                        <p class="mt-2 text-sm text-amber-300">Your licence expires soon — upload the renewed one to keep booking.</p>
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('verification.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <section class="card card-pad space-y-5">
                <h2 class="font-semibold">Driving licence</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="licence_number" label="Licence number" :value="$profile?->licence_number" required autocomplete="off" />
                    <x-field name="licence_expiry" label="Expiry date" type="date" :value="$profile?->licence_expiry?->toDateString()" required :min="now()->addDay()->toDateString()" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="licence_front" label="Photo — front{{ $profile ? ' (leave empty to keep)' : '' }}" type="file" accept="image/*,application/pdf" :required="! $profile" help="Clear, all four corners visible." />
                    <x-field name="licence_back" label="Photo — back (optional)" type="file" accept="image/*,application/pdf" />
                </div>
            </section>

            <section class="card card-pad space-y-5">
                <h2 class="font-semibold">Identity</h2>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field name="id_type" label="ID type" type="select" :value="$profile?->id_type ?? 'ic'" :options="\App\Models\DriverProfile::ID_TYPES" required />
                    <x-field name="id_number" label="IC / passport number" :value="$profile?->id_number" required autocomplete="off" />
                    <x-field name="date_of_birth" label="Date of birth" type="date" :value="$profile?->date_of_birth?->toDateString()" required :max="now()->subYears(config('compliance.min_driver_age'))->toDateString()" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="id_document" label="Photo of IC / passport{{ $profile ? ' (leave empty to keep)' : '' }}" type="file" accept="image/*,application/pdf" :required="! $profile" />
                    <x-field name="phone" label="Mobile number (optional)" type="tel" :value="$profile?->phone" placeholder="+60 12-345 6789" />
                </div>
            </section>

            <section class="card card-pad">
                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="consent" value="1" class="checkbox mt-0.5" required>
                    <span class="text-mute">I agree to EasyCar processing these documents to verify my identity and eligibility to drive, as described in the <a href="{{ route('legal.privacy') }}" class="text-white underline" target="_blank">Privacy Notice</a>. Documents are stored privately, encrypted where applicable, and seen only by our staff.</span>
                </label>
                @error('consent')<p class="field-error">{{ $message }}</p>@enderror
                <div class="mt-5 flex justify-end">
                    <button class="btn btn-primary btn-lg"><x-icon name="shield" class="size-5" /> {{ $profile ? 'Resubmit for verification' : 'Submit for verification' }}</button>
                </div>
            </section>
        </form>
    </div>
@endsection
