<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Verified reviews: a customer can rate each car on their own completed
 * booking, once, within config('reviews.window_days') of completion. They
 * can edit their review inside the same window.
 */
class ReviewService
{
    public function canReview(Booking $booking, User $user): bool
    {
        return $booking->user_id === $user->id
            && $booking->status === 'completed'
            && $booking->completed_at !== null
            && $booking->completed_at->gte(now()->subDays((int) config('reviews.window_days')));
    }

    /**
     * Cars on the booking the customer hasn't reviewed yet.
     *
     * @return Collection<int, Car>
     */
    public function pendingCars(Booking $booking, User $user): Collection
    {
        if (! $this->canReview($booking, $user)) {
            return collect();
        }

        $reviewed = $booking->reviews()->pluck('car_id')->all();

        return $booking->cars->reject(fn (Car $car) => in_array($car->id, $reviewed, true))->values();
    }

    /**
     * @param  array{rating: int, title?: ?string, body?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function submit(Booking $booking, Car $car, User $user, array $data): Review
    {
        if (! $this->canReview($booking, $user)) {
            throw ValidationException::withMessages(['rating' => ['You can review a car once your trip is completed (within '.config('reviews.window_days').' days).']]);
        }
        if (! $booking->cars->contains('id', $car->id)) {
            throw ValidationException::withMessages(['car_id' => ['That car wasn\'t on this booking.']]);
        }

        return Review::updateOrCreate(
            ['booking_id' => $booking->id, 'car_id' => $car->id],
            [
                'user_id' => $user->id,
                'rating' => $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
            ],
        );
    }

    /**
     * Published rating summary for a car: average, count and a 5→1 distribution.
     *
     * @return array{average: float, count: int, distribution: array<int, int>}
     */
    public function summary(Car $car): array
    {
        $counts = $car->reviews()->published()
            ->selectRaw('rating, count(*) as total')->groupBy('rating')
            ->pluck('total', 'rating');

        $distribution = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = (int) ($counts[$star] ?? 0);
        }

        $count = array_sum($distribution);
        $average = $count ? round(array_sum(array_map(fn ($s, $n) => $s * $n, array_keys($distribution), $distribution)) / $count, 1) : 0.0;

        return ['average' => $average, 'count' => $count, 'distribution' => $distribution];
    }
}
