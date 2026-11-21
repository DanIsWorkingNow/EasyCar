@extends('layouts.console')

@section('title', 'Edit '.$user->name)
@section('breadcrumb', 'Admin console / Users / '.$user->name)

@section('content')
    <x-page-header eyebrow="Access" :title="$user->name" :subtitle="$user->email" :back="route('admin.users.index')" />
    @include('admin.users._form')

    @unless ($user->consoleRoute())
        <section class="card mt-6 max-w-3xl" id="rewards">
            <div class="flex items-center justify-between border-b border-line px-6 py-4">
                <h2 class="flex items-center gap-2 font-semibold"><x-icon name="star" class="size-5 text-amber-300" /> EasyCar Rewards</h2>
                <span class="text-sm"><span class="font-semibold">{{ number_format($user->pointsBalance()) }}</span> points</span>
            </div>
            <form method="POST" action="{{ route('admin.users.points', $user) }}" class="flex flex-wrap items-end gap-3 px-6 py-4">
                @csrf
                <x-field name="points" label="Adjust by (±)" type="number" step="1" class="w-36" required />
                <x-field name="reason" label="Reason (shown to the customer)" class="min-w-60 flex-1" maxlength="150" required placeholder="e.g. Apology for delayed pick-up" />
                <button class="btn btn-primary">Apply</button>
            </form>
            <ul class="divide-y divide-line border-t border-line text-sm">
                @forelse ($user->loyaltyTransactions()->latest()->limit(10)->get() as $t)
                    <li class="flex items-center justify-between gap-3 px-6 py-2.5"><span class="text-mute">{{ $t->description }} <span class="text-xs text-subtle">· {{ $t->created_at->format('d M Y') }}</span></span><span class="font-semibold tabular-nums {{ $t->points >= 0 ? 'text-emerald-300' : 'text-red-300' }}">{{ $t->points >= 0 ? '+' : '' }}{{ number_format($t->points) }}</span></li>
                @empty
                    <li class="px-6 py-4 text-subtle">No points activity yet.</li>
                @endforelse
            </ul>
        </section>
    @endunless
@endsection
