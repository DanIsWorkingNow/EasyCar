<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * SUPERSEDES the CarFactory shipped in Level 1 Part 2 — adds plate_number
 * (FR-CAR-06) so factory-created cars in tests always have one, matching
 * production data after the backfill command runs.
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        return [
            'brand' => fake()->randomElement(['Perodua', 'Toyota', 'Honda', 'Nissan', 'Proton']),
            'model' => fake()->word(),
            'type' => fake()->randomElement(['Sedan', 'SUV', 'Hatchback', 'MPV']),
            'transmission' => fake()->randomElement(['Automatic', 'Manual']),
            'price_per_day' => fake()->randomFloat(2, 45, 165),
            'branch_id' => Branch::factory(),
            'photo' => null,
            'plate_number' => strtoupper(fake()->bothify('W? ####')),
        ];
    }
}
