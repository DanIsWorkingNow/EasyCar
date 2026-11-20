@extends('layouts.console')

@section('title', 'Reviews')
@section('breadcrumb', 'Operations / Reviews')

@section('content')
    <x-page-header eyebrow="Customer experience" title="Reviews" subtitle="Verified reviews from completed trips. Reply publicly, or hide a review that breaks the rules (abuse, personal data, off-topic)." />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <x-stat label="Average rating" :value="$total ? number_format($average, 2).' ★' : '—'" icon="star" />
        <x-stat label="Reviews" :value="$total" icon="chat" />
        <x-stat label="Awaiting reply" :value="$unanswered" icon="clock" />
    </div>

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach (['all' => 'All', 'unanswered' => 'Awaiting reply', 'low' => '1–2 stars', 'hidden' => 'Hidden'] as $key => $label)
            <a href="{{ route('ops.reviews.index', ['filter' => $key]) }}" class="btn btn-sm {{ $filter === $key ? 'btn-light' : 'btn-outline' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($reviews as $review)
            <article class="card card-pad {{ $review->is_published ? '' : 'opacity-70' }}" x-data="{ replying: false }">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <x-stars :rating="$review->rating" />
                            <a href="{{ route('cars.show', $review->car) }}#reviews" class="font-semibold hover:underline" target="_blank">{{ $review->car->name }}</a>
                            @unless ($review->is_published)<x-badge tone="neutral" icon="eye">Hidden</x-badge>@endunless
                        </div>
                        @if ($review->title)<p class="mt-2 font-medium">{{ $review->title }}</p>@endif
                        @if ($review->body)<p class="mt-1 text-sm leading-relaxed text-mute">{{ $review->body }}</p>@endif
                        <p class="mt-2 text-xs text-subtle">{{ $review->user?->name }} · booking {{ $review->booking?->reference }} · {{ $review->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline" @click="replying = ! replying"><x-icon name="chat" class="size-4" /> {{ $review->reply ? 'Edit reply' : 'Reply' }}</button>
                        <form method="POST" action="{{ route('ops.reviews.update', $review) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="is_published" value="{{ $review->is_published ? 0 : 1 }}">
                            <button class="btn btn-sm btn-quiet">{{ $review->is_published ? 'Hide' : 'Publish' }}</button>
                        </form>
                    </div>
                </div>

                @if ($review->reply)
                    <div class="mt-4 rounded-lg bg-white/[0.04] p-3 text-sm ring-1 ring-line" x-show="! replying">
                        <p class="text-xs font-semibold text-white/80">Reply from EasyCar · {{ $review->replied_at?->format('d M Y') }}</p>
                        <p class="mt-1 text-mute">{{ $review->reply }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('ops.reviews.update', $review) }}" class="mt-4 space-y-3" x-show="replying" x-cloak>
                    @csrf @method('PATCH')
                    <label for="reply-{{ $review->id }}" class="sr-only">Reply</label>
                    <textarea id="reply-{{ $review->id }}" name="reply" rows="3" maxlength="2000" class="input" placeholder="Thank them, or explain what you've done about the issue. Shown publicly.">{{ $review->reply }}</textarea>
                    <div class="flex gap-2">
                        <button class="btn btn-sm btn-primary">Post reply</button>
                        <button type="button" class="btn btn-sm btn-quiet" @click="replying = false">Cancel</button>
                    </div>
                </form>
            </article>
        @empty
            <x-empty-state icon="star" title="No reviews here" description="Customers can review a car once their trip is completed." />
        @endforelse
    </div>
    <div class="mt-6">{{ $reviews->links() }}</div>
@endsection
