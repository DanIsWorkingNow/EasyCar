<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+3 days', '+10 days');
        $end = (clone $start)->modify('+' . fake()->numberBetween(1, 5) . ' days');

        return [
            'user_id' => User::factory(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_days' => $start->diff($end)->days + 1,
            'status' => 'pending',
            'total_price' => 0,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }
}
