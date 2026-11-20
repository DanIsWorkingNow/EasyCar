@extends('layouts.console')

@section('title', 'Driver verifications')
@section('breadcrumb', 'Operations / Driver verifications')

@section('content')
    <x-page-header eyebrow="Trust" title="Driver verifications" subtitle="Check each customer's licence and ID before their first booking is confirmed." />

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (['pending' => 'Under review', 'rejected' => 'Needs attention', 'verified' => 'Verified', 'all' => 'All'] as $key => $label)
            <a href="{{ route('ops.verifications.index', ['status' => $key]) }}" class="btn btn-sm {{ $status === $key ? 'btn-light' : 'btn-outline' }}">
                {{ $label }} @if ($key !== 'all')<span class="opacity-70">{{ $counts[$key] ?? 0 }}</span>@endif
            </a>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="table-ec">
            <thead><tr><th>Customer</th><th>Submitted</th><th>Licence expiry</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($profiles as $profile)
                    <tr>
                        <td><div class="flex items-center gap-3"><x-avatar :user="$profile->user" /><div><p class="font-medium">{{ $profile->user->name }}</p><p class="text-xs text-subtle">{{ $profile->user->email }}</p></div></div></td>
                        <td class="text-mute">{{ $profile->submitted_at->format('d M Y, g:i A') }}<span class="block text-xs text-subtle">{{ $profile->submitted_at->diffForHumans() }}</span></td>
                        <td class="{{ $profile->licence_expiry->lt(now()->addDays(60)) ? 'text-amber-300' : 'text-mute' }}">{{ $profile->licence_expiry->format('d M Y') }}</td>
                        <td><x-badge :tone="$profile->status->tone()">{{ $profile->status->label() }}</x-badge></td>
                        <td class="text-right"><a href="{{ route('ops.verifications.show', $profile) }}" class="btn btn-sm {{ $profile->status->value === 'pending' ? 'btn-primary' : 'btn-outline' }}">{{ $profile->status->value === 'pending' ? 'Review' : 'Open' }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="shield" title="Nothing to review" description="New submissions will appear here." class="border-0" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $profiles->links() }}</div>
@endsection
