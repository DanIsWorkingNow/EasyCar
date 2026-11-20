@php
    $reviewService = app(\App\Services\ReviewService::class);
    $canReview = $reviewService->canReview($booking, auth()->user());
    $existingReviews = $booking->reviews->keyBy('car_id');
@endphp
@if ($canReview || $existingReviews->isNotEmpty())
    <section id="reviews" class="card card-pad scroll-mt-28" aria-labelledby="reviews-heading">
        <div class="flex items-center gap-3">
            <span class="grid size-9 place-items-center rounded-full bg-amber-400/15 text-amber-300"><x-icon name="star" solid class="size-5" /></span>
            <div>
                <h2 id="reviews-heading" class="text-lg font-semibold">Rate your trip</h2>
                <p class="text-sm text-mute">Your review helps other renters choose — it appears on the car's page with your first name.</p>
            </div>
        </div>

        <div class="mt-6 space-y-6">
            @foreach ($booking->cars as $car)
                @php $existing = $existingReviews->get($car->id); @endphp
                <div class="rounded-xl p-4 ring-1 ring-line">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-16 shrink-0 overflow-hidden rounded bg-ink-700"><x-car-photo :car="$car" /></div>
                        <p class="flex-1 font-semibold">{{ $car->name }}</p>
                        @if ($existing)
                            <x-stars :rating="$existing->rating" />
                        @endif
                    </div>

                    @if ($existing && ! $canReview)
                        @if ($existing->title)<p class="mt-3 font-medium">{{ $existing->title }}</p>@endif
                        @if ($existing->body)<p class="mt-1 text-sm text-mute">{{ $existing->body }}</p>@endif
                    @elseif ($canReview)
                        <form method="POST" action="{{ route('reviews.store', $booking) }}" class="mt-4 space-y-3"
                              x-data="{ rating: {{ (int) old('car_id') === $car->id ? (int) old('rating') : (int) ($existing?->rating ?? 0) }}, hover: 0 }">
                            @csrf
                            <input type="hidden" name="car_id" value="{{ $car->id }}">
                            <input type="hidden" name="rating" :value="rating" value="{{ $existing?->rating }}">
                            <fieldset>
                                <legend class="label">Your rating</legend>
                                <div class="flex items-center gap-1" @mouseleave="hover = 0">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}"
                                                class="rounded p-0.5 transition hover:scale-110" aria-label="{{ $i }} {{ Str::plural('star', $i) }}" :aria-pressed="(rating === {{ $i }}).toString()">
                                            <x-icon name="star" solid class="size-7" ::class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-white/15'" />
                                        </button>
                                    @endfor
                                    <span class="ml-2 text-sm text-mute" x-text="['', 'Poor', 'Fair', 'Good', 'Great', 'Excellent'][hover || rating]"></span>
                                </div>
                            </fieldset>
                            <input type="text" name="title" maxlength="120" placeholder="Sum it up (optional)" value="{{ (int) old('car_id') === $car->id ? old('title') : $existing?->title }}" class="input">
                            <textarea name="body" rows="3" maxlength="2000" placeholder="How was the car? Clean, comfortable, easy pick-up? (optional)" class="input">{{ (int) old('car_id') === $car->id ? old('body') : $existing?->body }}</textarea>
                            @if ((int) old('car_id') === $car->id && $errors->any())
                                <p class="text-sm text-red-300" role="alert">{{ $errors->first() }}</p>
                            @endif
                            <button class="btn btn-primary" :disabled="! rating">{{ $existing ? 'Update review' : 'Post review' }}</button>
                        </form>
                    @endif

                    @if ($existing?->reply)
                        <div class="mt-3 rounded-lg bg-white/[0.04] p-3 text-sm ring-1 ring-line">
                            <p class="text-xs font-semibold text-white/80">Reply from EasyCar</p>
                            <p class="mt-1 text-mute">{{ $existing->reply }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
