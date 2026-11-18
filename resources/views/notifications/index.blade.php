@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="container-ec max-w-3xl">
        <x-page-header eyebrow="Inbox" title="Notifications">
            <x-slot:actions>
                @if (auth()->user()->unreadNotifications()->exists())
                    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-outline btn-sm">Mark all as read</button></form>
                @endif
            </x-slot:actions>
        </x-page-header>

        @if ($notifications->isEmpty())
            <x-empty-state icon="bell" title="You're all caught up" description="Booking confirmations, payment receipts and refunds will show up here." />
        @else
            <ul class="card divide-y divide-line overflow-hidden">
                @foreach ($notifications as $note)
                    <li>
                        <a href="{{ route('notifications.open', $note->id) }}" class="flex items-start gap-4 px-5 py-4 hover:bg-white/[0.03]">
                            <span class="mt-2 size-2 shrink-0 rounded-full {{ $note->read_at ? 'bg-white/15' : 'bg-brand' }}"></span>
                            <div class="min-w-0 flex-1">
                                <p class="{{ $note->read_at ? 'text-mute' : 'font-medium text-white' }}">{{ $note->data['message'] ?? 'Booking update' }}</p>
                                <p class="mt-0.5 text-xs text-subtle">{{ $note->created_at->format('d M Y, g:i A') }} · {{ $note->created_at->diffForHumans() }}</p>
                            </div>
                            <x-icon name="chevron-right" class="mt-1 size-4 text-subtle" />
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-6">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
